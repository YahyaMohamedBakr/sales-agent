<?php

namespace App\Domains\Agent\Agents;

use App\Domains\Agent\Contracts\AgentInterface;
use App\Domains\Agent\Contracts\SmartRouterInterface;
use App\Domains\Agent\DTOs\AnalysisResult;
use App\Domains\Agent\DTOs\ModelConfig;
use App\Domains\Agent\Enums\AIProvider;
use App\Domains\Conversation\Repositories\ConversationRepositoryInterface;
use App\Domains\Lead\Repositories\LeadRepositoryInterface;
use Illuminate\Support\Facades\Log;

class SupportAgent implements AgentInterface
{
    private ModelConfig $modelConfig;

    private array $escalateKeywords = [
        'موظف', 'بشر', 'بشري', 'human', 'agent',
        'مكالمة', 'اتصال', 'call', 'speak',
        'مدير', 'manager', 'support',
        'شكوى', ' complain', 'مشكلة',
        'كلمني', 'اتصل', 'call me',
    ];

    public function __construct(
        private SmartRouterInterface $router,
        private LeadRepositoryInterface $leads,
        private ConversationRepositoryInterface $conversations,
    ) {
        $this->modelConfig = $this->resolveConfig();
    }

    public function name(): string
    {
        return 'support';
    }

    public function shouldHandle(string $message, array $context = []): bool
    {
        $lead = $context['lead'] ?? null;
        if (!$lead) return false;

        if ($lead->status === 'escalated') return true;

        $msg = mb_strtolower(trim($message));
        foreach ($this->escalateKeywords as $kw) {
            if (mb_strpos($msg, mb_strtolower($kw)) !== false) {
                return true;
            }
        }

        return false;
    }

    public function handle(string $message, array $context = []): string
    {
        $lead = $context['lead'] ?? null;
        if (!$lead) return '';

        $this->conversations->logMessage($lead->id, 'messenger', $message, 'inbound');

        if ($lead->status !== 'escalated') {
            $this->leads->markAs($lead->id, 'escalated');
            Log::info('SupportAgent: lead escalated', ['lead_id' => $lead->id]);
        }

        $reply = $this->generateReply($lead, $message, $context);

        $this->conversations->logMessage($lead->id, 'messenger', $reply, 'outbound');

        return $reply;
    }

    public function analyze(string $message): AnalysisResult
    {
        return $this->router->analyze($message, preferred: $this->modelConfig->provider);
    }

    public function setModelConfig(ModelConfig $config): void
    {
        $this->modelConfig = $config;
    }

    public function getModelConfig(): ModelConfig
    {
        return $this->modelConfig;
    }

    private function generateReply($lead, string $message, array $context): string
    {
        $conversations = $this->conversations->findRecentByLead($lead->id, 5);
        $history = $conversations->map(fn ($c) => "[{$c->direction}] {$c->message}")->implode("\n");

        $systemPrompt = <<<PROMPT
أنت وكيل دعم تقوم بتحويل العميل إلى فريق الدعم البشري.

معلومات العميل:
- الاسم: {$lead->name}
- الحالة: محول للدعم البشري

تاريخ المحادثة:
{$history}

القواعد:
- اعتذر بلطف عن التأخير أو الإزعاج
- أكد للعميل أنه سيتم التواصل معه من فريق الدعم البشري قريباً
- اطلب منه توضيح المشكلة باختصار عشان تنقلها للفريق
- كن مهذباً ومتفهماً
- رد بجملتين إلى ٣ جمل
- استخدم لغة العميل
PROMPT;

        try {
            $response = $this->router->chat(
                messages: [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => "رسالة العميل:\n{$message}"],
                ],
                preferred: $this->modelConfig->provider,
            );

            return $response->content;
        } catch (\Throwable $e) {
            Log::warning('SupportAgent: LLM failed, using fallback', ['error' => $e->getMessage()]);
            return "شكراً لتواصلك! تم تحويل طلبك لفريق الدعم البشري. سنتواصل معك في أقرب وقت ممكن 😊";
        }
    }

    private function resolveConfig(): ModelConfig
    {
        $providerName = config('agent.agents.support.provider', 'smart');

        $provider = $providerName === 'smart'
            ? AIProvider::OpenAI
            : AIProvider::tryFrom($providerName) ?? AIProvider::OpenAI;

        return new ModelConfig(
            provider: $provider,
            model: $provider->defaultModel(),
            temperature: 0.7,
            maxTokens: 300,
        );
    }
}
