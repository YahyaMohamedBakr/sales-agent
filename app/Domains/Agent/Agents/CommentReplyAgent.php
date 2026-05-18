<?php

namespace App\Domains\Agent\Agents;

use App\Domains\Agent\Contracts\AgentInterface;
use App\Domains\Agent\Contracts\SmartRouterInterface;
use App\Domains\Agent\DTOs\AnalysisResult;
use App\Domains\Agent\DTOs\ModelConfig;
use App\Domains\Agent\Enums\AIProvider;
use App\Domains\Integration\Contracts\SocialPlatformInterface;
use App\Domains\Lead\Repositories\LeadRepositoryInterface;
use App\Domains\Conversation\Repositories\ConversationRepositoryInterface;
use Illuminate\Support\Facades\Log;

class CommentReplyAgent implements AgentInterface
{
    private ModelConfig $modelConfig;

    public function __construct(
        private SmartRouterInterface $router,
        private SocialPlatformInterface $platform,
        private LeadRepositoryInterface $leads,
        private ConversationRepositoryInterface $conversations,
    ) {
        $this->modelConfig = $this->resolveConfig();
    }

    public function name(): string
    {
        return 'comment_reply';
    }

    public function shouldHandle(string $message, array $context = []): bool
    {
        $msg = trim(mb_strtolower($message));

        if (empty($msg) || mb_strlen($msg) < 2) {
            return false;
        }

        $skipKeywords = ['spam', ' scam', 'follow', 'subscribe'];
        foreach ($skipKeywords as $kw) {
            if (str_contains($msg, $kw)) return false;
        }

        return true;
    }

    public function handle(string $message, array $context = []): string
    {
        $analysis = $this->analyze($message);

        $reply = $analysis->suggestedReply ?? $this->generateReply($message, $analysis);

        $this->postReply($reply, $context, $analysis);

        $this->logAction($message, $reply, $analysis, $context);

        return $reply;
    }

    public function analyze(string $message): AnalysisResult
    {
        return $this->router->analyze(
            text: $message,
            preferred: $this->modelConfig->provider,
        );
    }

    public function setModelConfig(ModelConfig $config): void
    {
        $this->modelConfig = $config;
    }

    public function getModelConfig(): ModelConfig
    {
        return $this->modelConfig;
    }

    private function generateReply(string $message, AnalysisResult $analysis): string
    {
        $systemPrompt = <<<'PROMPT'
أنت مساعد مبيعات ذكي لصفحة فيسبوك. رد على تعليق العميل بطريقة لطيفة ومهنية.

القواعد:
- رد باللغة العربية الفصحى أو العامية حسب لغة العميل
- كن ودوداً ومفيداً
- إذا سأل عن سعر → جاوب واعرض إرسال التفاصيل عبر المسنجر
- إذا طلب معلومات → قدمها باختصار
- لا تطلب معلومات حساسة (رقم بطاقة، كلمة سر)
- ختم الرد بعرض مساعدة إضافية
- اجعل الرد قصيراً (جملتين إلى ٣ جمل)
- إذا كان التعليق يستحق متابعة → قل له "أرسلنا لك رسالة خاصة 😊"
PROMPT;

        $response = $this->router->chat(
            messages: [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => "تعليق جديد:\n{$message}"],
            ],
            preferred: $this->modelConfig->provider,
        );

        return $response->content;
    }

    private function postReply(string $reply, array $context, AnalysisResult $analysis): void
    {
        $commentId = $context['comment_id'] ?? null;
        if (!$commentId) return;

        try {
            $this->platform->replyToComment($commentId, $reply);
        } catch (\Throwable $e) {
            Log::error('Failed to post comment reply', [
                'comment_id' => $commentId,
                'error' => $e->getMessage(),
            ]);
        }

        if ($analysis->shouldDM || $analysis->needsFollowUp) {
            $lead = $context['lead'] ?? null;
            if ($lead && $lead->psid) {
                try {
                    $dmMessage = str_starts_with($reply, 'أرسلنا لك رسالة خاصة')
                        ? "مرحباً {$context['from_name']}! شكراً لتواصلك. كيف أقدر أساعدك؟ 😊"
                        : "مرحباً {$context['from_name']}! شفت تعليقك على المنشور وحبيت أساعدك بشكل أفضل هنا 💬";

                    $this->platform->sendMessage($lead->psid, $dmMessage);

                    $this->conversations->logMessage(
                        $lead->id,
                        'messenger',
                        $dmMessage,
                        'outbound',
                        ['trigger' => 'comment_reply', 'comment_id' => $commentId],
                    );
                } catch (\Throwable $e) {
                    Log::warning('Failed to send DM', ['error' => $e->getMessage()]);
                }
            }
        }
    }

    private function logAction(string $message, string $reply, AnalysisResult $analysis, array $context): void
    {
        $lead = $context['lead'] ?? null;

        if ($lead) {
            $this->conversations->logMessage(
                $lead->id,
                'comment',
                $message,
                'inbound',
                ['intent' => $analysis->intent, 'sentiment' => $analysis->sentiment],
            );

            $this->conversations->logMessage(
                $lead->id,
                'comment',
                $reply,
                'outbound',
                ['is_agent_reply' => true],
            );
        }

        Log::info('CommentReplyAgent: handled', [
            'intent' => $analysis->intent,
            'sentiment' => $analysis->sentiment,
            'should_dm' => $analysis->shouldDM,
        ]);
    }

    private function resolveConfig(): ModelConfig
    {
        $providerName = config('agent.agents.comment_reply.provider', 'smart');

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
