<?php

namespace App\Domains\Agent\AIProviders;

use App\Domains\Agent\DTOs\AIResponse;
use App\Domains\Agent\DTOs\AnalysisResult;
use App\Domains\Agent\DTOs\ModelConfig;
use App\Domains\Agent\Enums\AIProvider;
use App\Domains\Agent\Enums\ProviderStatus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenRouterProvider extends BaseProvider
{
    private ?string $apiKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('agent.providers.openrouter.api_key') ?: env('OPENROUTER_API_KEY');
        $this->baseUrl = config('agent.providers.openrouter.base_url', 'https://openrouter.ai/api/v1');
    }

    public function provider(): AIProvider
    {
        return AIProvider::OpenRouter;
    }

    public function chat(array $messages, ModelConfig $config): AIResponse
    {
        $start = hrtime(true);

        $payload = $this->buildChatPayload($messages, $config);

        $response = Http::withToken($this->apiKey)
            ->withHeaders([
                'HTTP-Referer' => config('app.url'),
                'X-Title' => config('app.name'),
            ])
            ->timeout(30)
            ->post("{$this->baseUrl}/chat/completions", $payload);

        $elapsed = (hrtime(true) - $start) / 1_000_000;

        if (!$response->successful()) {
            $this->trackFailure($response->body());
            throw new \RuntimeException("OpenRouter error: {$response->body()}");
        }

        $data = $response->json();
        $this->trackSuccess((int) $elapsed);

        return new AIResponse(
            content: $data['choices'][0]['message']['content'] ?? '',
            model: $data['model'] ?? $config->model,
            provider: $this->provider()->value,
            inputTokens: $data['usage']['prompt_tokens'] ?? 0,
            outputTokens: $data['usage']['completion_tokens'] ?? 0,
            processingTimeMs: (int) $elapsed,
            finishReason: $data['choices'][0]['finish_reason'] ?? null,
        );
    }

    public function analyze(string $text, ModelConfig $config): AnalysisResult
    {
        $messages = $this->buildAnalysisPrompt($text);

        try {
            $response = $this->chat($messages, $config);
            return $this->parseAnalysisResponse($response->content);
        } catch (\Throwable $e) {
            Log::error('OpenRouter analysis failed', ['error' => $e->getMessage()]);
            return AnalysisResult::fromArray(['error' => $e->getMessage()]);
        }
    }

    public function embed(string $text): array
    {
        throw new \RuntimeException('OpenRouter does not support embeddings');
    }

    public function models(): array
    {
        return AIProvider::OpenRouter->availableModels();
    }

    protected function checkHealth(): ProviderStatus
    {
        if (!$this->apiKey) {
            return ProviderStatus::NotConfigured;
        }

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(5)
                ->get("{$this->baseUrl}/models");

            if ($response->successful()) return ProviderStatus::Active;
            if ($response->status() === 429) return ProviderStatus::RateLimited;

            return ProviderStatus::Unavailable;
        } catch (\Throwable $e) {
            return ProviderStatus::Unavailable;
        }
    }

    protected function getRateLimitPerMinute(): ?int
    {
        return 200;
    }

    protected function getRateLimitPerHour(): ?int
    {
        return 5000;
    }
}
