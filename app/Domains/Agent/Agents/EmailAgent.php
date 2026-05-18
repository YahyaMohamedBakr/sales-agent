<?php

namespace App\Domains\Agent\Agents;

use App\Domains\Agent\Contracts\AgentInterface;
use App\Domains\Agent\Contracts\SmartRouterInterface;
use App\Domains\Agent\DTOs\AnalysisResult;
use App\Domains\Agent\DTOs\ModelConfig;
use App\Domains\Agent\Enums\AIProvider;
use App\Domains\Integration\Services\EmailService;
use App\Domains\Lead\Repositories\LeadRepositoryInterface;
use Illuminate\Support\Facades\Log;

class EmailAgent implements AgentInterface
{
    private ModelConfig $modelConfig;

    public function __construct(
        private SmartRouterInterface $router,
        private LeadRepositoryInterface $leads,
        private EmailService $email,
    ) {
        $this->modelConfig = new ModelConfig(
            provider: AIProvider::OpenAI,
            model: 'gpt-4o-mini',
            temperature: 0.7,
            maxTokens: 300,
        );
    }

    public function name(): string
    {
        return 'email_agent';
    }

    public function shouldHandle(string $message, array $context = []): bool
    {
        return false;
    }

    public function handle(string $message, array $context = []): string
    {
        return '';
    }

    public function analyze(string $message): AnalysisResult
    {
        return AnalysisResult::fromArray(['error' => 'Not implemented']);
    }

    public function setModelConfig(ModelConfig $config): void
    {
        $this->modelConfig = $config;
    }

    public function getModelConfig(): ModelConfig
    {
        return $this->modelConfig;
    }

    public function sendWelcome(string $leadId): void
    {
        $lead = $this->leads->findById($leadId);
        if (!$lead || !$lead->email) return;

        try {
            $this->email->sendWelcome($lead->email, $lead->name ?? 'عميلنا');
            Log::info('EmailAgent: welcome sent', ['lead_id' => $leadId, 'email' => $lead->email]);
        } catch (\Throwable $e) {
            Log::error('EmailAgent: welcome failed', ['error' => $e->getMessage()]);
        }
    }

    public function sendQualificationConfirmation(string $leadId): void
    {
        $lead = $this->leads->findById($leadId);
        if (!$lead || !$lead->email) return;

        try {
            $this->email->sendQualificationConfirmation($lead->email, $lead->name ?? 'عميلنا');
            Log::info('EmailAgent: qualification confirmation sent', ['lead_id' => $leadId]);
        } catch (\Throwable $e) {
            Log::error('EmailAgent: qualification failed', ['error' => $e->getMessage()]);
        }
    }

    public function generateAndSendOffer(string $leadId, string $productContext = ''): void
    {
        $lead = $this->leads->findById($leadId);
        if (!$lead || !$lead->email) return;

        try {
            $prompt = "اكتب عرض مبيعات جذاب وقصير (٣-٤ جمل) بالعربية لعميل اسمه {$lead->name}.";
            if ($productContext) {
                $prompt .= " معلومات عن المنتج: {$productContext}";
            }

            $response = $this->router->chat(
                messages: [
                    ['role' => 'system', 'content' => 'أنت كاتب عروض مبيعات محترف. اكتب عرض قصير وجذاب.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                preferred: $this->modelConfig->provider,
            );

            $this->email->sendOffer($lead->email, $lead->name ?? 'عميلنا', $response->content);
            Log::info('EmailAgent: offer sent', ['lead_id' => $leadId]);
        } catch (\Throwable $e) {
            Log::error('EmailAgent: offer failed', ['error' => $e->getMessage()]);
        }
    }
}
