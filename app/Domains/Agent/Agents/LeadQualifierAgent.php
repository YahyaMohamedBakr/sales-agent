<?php

namespace App\Domains\Agent\Agents;

use App\Domains\Agent\Contracts\AgentInterface;
use App\Domains\Agent\Contracts\SmartRouterInterface;
use App\Domains\Agent\DTOs\AnalysisResult;
use App\Domains\Agent\DTOs\ModelConfig;
use App\Domains\Agent\Enums\AIProvider;
use App\Domains\Lead\Enums\LeadScoreEvent;
use App\Domains\Lead\Repositories\LeadRepositoryInterface;
use App\Domains\Conversation\Repositories\ConversationRepositoryInterface;
use Illuminate\Support\Facades\Log;

class LeadQualifierAgent implements AgentInterface
{
    private ModelConfig $modelConfig;

    public function __construct(
        private SmartRouterInterface $router,
        private LeadRepositoryInterface $leads,
        private ConversationRepositoryInterface $conversations,
    ) {
        $this->modelConfig = $this->resolveConfig();
    }

    public function name(): string
    {
        return 'lead_qualifier';
    }

    public function shouldHandle(string $message, array $context = []): bool
    {
        $lead = $context['lead'] ?? null;
        if (!$lead) return false;

        return in_array($lead->status, ['new', 'contacted', 'qualifying']);
    }

    public function handle(string $message, array $context = []): string
    {
        $lead = $context['lead'] ?? null;
        if (!$lead) return '';

        $this->conversations->logMessage($lead->id, 'messenger', $message, 'inbound');

        $analysis = $this->router->analyze($message);
        $this->updateLeadScore($lead, $message, $analysis);

        $reply = $this->generateReply($lead, $message, $analysis, $context);

        $this->conversations->logMessage($lead->id, 'messenger', $reply, 'outbound');

        return $reply;
    }

    public function analyze(string $message): AnalysisResult
    {
        return $this->router->analyze($message);
    }

    public function setModelConfig(ModelConfig $config): void
    {
        $this->modelConfig = $config;
    }

    public function getModelConfig(): ModelConfig
    {
        return $this->modelConfig;
    }

    private function generateReply($lead, string $message, AnalysisResult $analysis, array $context): string
    {
        $conversations = $this->conversations->findRecentByLead($lead->id, 10);

        $history = $conversations->map(fn ($c) => "[{$c->direction}] {$c->message}")->implode("\n");

        $missingInfo = [];
        if (!$lead->phone) $missingInfo[] = 'رقم الهاتف';
        if (!$lead->email) $missingInfo[] = 'البريد الإلكتروني';
        if (!$lead->name) $missingInfo[] = 'الاسم';

        $infoStr = empty($missingInfo)
            ? 'لدينا جميع معلوماته'
            : 'نحتاج: ' . implode(', ', $missingInfo);

        $systemPrompt = <<<PROMPT
أنت مساعد تأهيل عملاء ذكي. هدفك جمع معلومات العميل خطوة بخطوة.

معلومات العميل الحالية:
- الاسم: {$lead->name}
- الهاتف: {$lead->phone}
- الإيميل: {$lead->email}
- النقاط: {$lead->score}/100
- {$infoStr}

تاريخ المحادثة:
{$history}

القواعد:
- كن ودوداً بلغة العميل
- اسأل عن معلومة واحدة فقط كل مرة (لا تكلف)
- ابدأ بالترحيب، ثم اسأل عن احتياجه
- بعد الإجابة، اسأل عن وسيلة تواصل (واتساب أو إيميل)
- لو شارك رقم الهاتف → اشكره وقل له هنتواصل قريباً
- لو عنده كل المعلومات → أخبره أنه تم تسجيل بياناته
- لا تسأل عن الاسم إذا كان معروفاً
- رد بجملتين إلى ٣ جمل فقط
PROMPT;

        $response = $this->router->chat(
            messages: [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => "رسالة العميل:\n{$message}"],
            ],
            preferred: $this->modelConfig->provider,
        );

        return $response->content;
    }

    private function updateLeadScore($lead, string $message, AnalysisResult $analysis): void
    {
        $scoreGained = 0;

        // Phone pattern
        if (preg_match('/0?5[0-9]{8,9}/', $message)) {
            preg_match('/0?5[0-9]{8,9}/', $message, $matches);
            $this->leads->update($lead->id, ['phone' => $matches[0]]);
            $scoreGained += LeadScoreEvent::PhoneShared->points();
            Log::info('LeadQualifier: phone extracted', ['lead_id' => $lead->id]);
        }

        // Email pattern
        if (preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $message)) {
            preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $message, $matches);
            $this->leads->update($lead->id, ['email' => $matches[0]]);
            $scoreGained += LeadScoreEvent::EmailShared->points();
            Log::info('LeadQualifier: email extracted', ['lead_id' => $lead->id]);
        }

        // City/area pattern
        $cities = ['الرياض', 'جدة', 'مكة', 'المدينة', 'الدمام', 'الخبر', 'القصيم', 'تبوك', 'أبها', 'حائل', 'نجران', 'جازان'];
        foreach ($cities as $city) {
            if (mb_strpos($message, $city) !== false) {
                $this->leads->addFieldValue($lead->id, 'city', $city);
                $scoreGained += LeadScoreEvent::SharedCity->points();
                break;
            }
        }

        // Name extraction (if lead has no name and message starts with name-like pattern)
        if (!$lead->name && mb_strlen($message) < 20 && !preg_match('/[0-9]/', $message)) {
            $words = array_filter(explode(' ', $message));
            $firstName = reset($words);
            if ($firstName && mb_strlen($firstName) > 1) {
                $this->leads->update($lead->id, ['name' => $firstName]);
                $scoreGained += LeadScoreEvent::RepliedQualifying->points();
            }
        }

        // Interaction score (for any engaging message)
        $convCount = $this->conversations->findByLead($lead->id)->count();
        if ($convCount >= 3) {
            $scoreGained += LeadScoreEvent::Interacted->points();
        }

        if ($scoreGained > 0) {
            $newScore = min(100, $lead->score + $scoreGained);
            $this->leads->updateScore($lead->id, $newScore);
        }
    }

    private function resolveConfig(): ModelConfig
    {
        $providerName = config('agent.agents.lead_qualifier.provider', 'smart');

        $provider = $providerName === 'smart'
            ? AIProvider::OpenAI
            : AIProvider::tryFrom($providerName) ?? AIProvider::OpenAI;

        return new ModelConfig(
            provider: $provider,
            model: $provider->defaultModel(),
            temperature: 0.7,
            maxTokens: 500,
        );
    }
}
