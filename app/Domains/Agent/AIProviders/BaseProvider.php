<?php

namespace App\Domains\Agent\AIProviders;

use App\Domains\Agent\Contracts\AIProviderInterface;
use App\Domains\Agent\DTOs\AIResponse;
use App\Domains\Agent\DTOs\AnalysisResult;
use App\Domains\Agent\DTOs\ModelConfig;
use App\Domains\Agent\DTOs\ProviderHealth;
use App\Domains\Agent\Enums\ProviderStatus;
    use Illuminate\Support\Facades\Cache;
    use Illuminate\Support\Facades\Log;

abstract class BaseProvider implements AIProviderInterface
{
    private ?ProviderHealth $cachedHealth = null;

    protected array $rateLimitCounters = [];
    protected ?\DateTimeImmutable $lastSuccessAt = null;
    protected ?\DateTimeImmutable $lastFailureAt = null;
    protected ?string $lastError = null;
    protected array $latencies = [];

    public function isAvailable(): bool
    {
        return $this->health()->isAvailable();
    }

    public function health(): ProviderHealth
    {
        if ($this->cachedHealth !== null) {
            return $this->cachedHealth;
        }

        $this->cachedHealth = new ProviderHealth(
            provider: $this->provider()->value,
            status: $this->checkHealth(),
            requestsThisMinute: $this->getRequestsThisMinute(),
            requestsThisHour: $this->getRequestsThisHour(),
            rateLimitPerMinute: $this->getRateLimitPerMinute(),
            rateLimitPerHour: $this->getRateLimitPerHour(),
            averageLatencyMs: $this->getAverageLatency(),
            lastSuccessAt: $this->lastSuccessAt,
            lastFailureAt: $this->lastFailureAt,
            lastError: $this->lastError,
        );

        return $this->cachedHealth;
    }

    abstract protected function checkHealth(): ProviderStatus;

    abstract protected function getRateLimitPerMinute(): ?int;

    abstract protected function getRateLimitPerHour(): ?int;

    protected function trackSuccess(int $latencyMs): void
    {
        $this->lastSuccessAt = new \DateTimeImmutable();
        $this->lastError = null;
        $this->latencies[] = $latencyMs;
        $this->incrementRateCounter();
    }

    protected function trackFailure(string $error): void
    {
        $this->lastFailureAt = new \DateTimeImmutable();
        $this->lastError = $error;
        Log::warning("AI Provider {$this->provider()->value} failed", [
            'error' => $error,
            'provider' => $this->provider()->value,
        ]);
    }

    protected function getRequestsThisMinute(): int
    {
        $key = "rate_limit_{$this->provider()->value}_minute";
        return (int) Cache::get($key, 0);
    }

    protected function getRequestsThisHour(): int
    {
        $key = "rate_limit_{$this->provider()->value}_hour";
        return (int) Cache::get($key, 0);
    }

    protected function incrementRateCounter(): void
    {
        $minKey = "rate_limit_{$this->provider()->value}_minute";
        $hourKey = "rate_limit_{$this->provider()->value}_hour";

        if (!Cache::has($minKey)) {
            Cache::put($minKey, 1, 60);
        } else {
            Cache::increment($minKey);
        }

        if (!Cache::has($hourKey)) {
            Cache::put($hourKey, 1, 3600);
        } else {
            Cache::increment($hourKey);
        }
    }

    protected function getAverageLatency(): ?float
    {
        if (empty($this->latencies)) {
            return null;
        }
        return array_sum($this->latencies) / count($this->latencies);
    }

    protected function buildHttpClient(): \Illuminate\Http\Client\PendingRequest
    {
        return \Illuminate\Support\Facades\Http::timeout(30)
            ->withHeaders([
                'Content-Type' => 'application/json',
            ]);
    }

    protected function buildChatPayload(array $messages, ModelConfig $config): array
    {
        return [
            'model' => $config->model,
            'messages' => $messages,
            'temperature' => $config->temperature,
            'max_tokens' => $config->maxTokens,
            ...($config->topP ? ['top_p' => $config->topP] : []),
            ...($config->stopSequences ? ['stop' => $config->stopSequences] : []),
        ];
    }

    protected function buildAnalysisPrompt(string $text): array
    {
        return [
            [
                'role' => 'system',
                'content' => <<<'PROMPT'
You are an AI sales assistant. Analyze the following customer message and return a JSON object with:
- intent: the main intent (pricing_inquiry, product_question, complaint, purchase_intent, greeting, spam, other)
- language: detected language code (ar, en, etc.)
- sentiment: score from -1.0 to 1.0
- entities: list of mentioned entities (products, prices, etc.)
- score: lead score 0-100
- suggested_reply: a suggested reply in the same language
- needs_follow_up: boolean
- should_dm: boolean (should we move to private message?)
- extracted_info: object with any extracted information (phone, email, etc.)
PROMPT
            ],
            [
                'role' => 'user',
                'content' => $text,
            ],
        ];
    }

    protected function parseAnalysisResponse(string $content): AnalysisResult
    {
        $cleaned = trim($content);
        $cleaned = preg_replace('/^```json\s*/i', '', $cleaned);
        $cleaned = preg_replace('/^```\s*/i', '', $cleaned);
        $cleaned = preg_replace('/\s*```$/i', '', $cleaned);

        $data = json_decode($cleaned, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::warning('Failed to parse analysis response', [
                'raw' => $content,
                'error' => json_last_error_msg(),
            ]);
            return AnalysisResult::fromArray(['error' => 'Failed to parse analysis']);
        }

        return AnalysisResult::fromArray($data);
    }
}
