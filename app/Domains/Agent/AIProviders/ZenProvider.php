<?php

namespace App\Domains\Agent\AIProviders;

use App\Domains\Agent\DTOs\AIResponse;
use App\Domains\Agent\DTOs\AnalysisResult;
use App\Domains\Agent\DTOs\ModelConfig;
use App\Domains\Agent\Enums\AIProvider;
use App\Domains\Agent\Enums\ProviderStatus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZenProvider extends BaseProvider
{
    private ?string $apiKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('agent.providers.zen.api_key') ?: env('ZEN_API_KEY');
        $this->baseUrl = config('agent.providers.zen.base_url', 'https://opencode.ai/zen/v1');
    }

    public function provider(): AIProvider
    {
        return AIProvider::Zen;
    }

    public function chat(array $messages, ModelConfig $config): AIResponse
    {
        $start = hrtime(true);

        $payload = [
            'model' => $config->model,
            'messages' => $messages,
            'temperature' => $config->temperature,
            'max_tokens' => $config->maxTokens,
            'stream' => false,
        ];

        $http = Http::timeout(60)
            ->withHeaders(['Content-Type' => 'application/json']);

        if ($this->apiKey) {
            $http = $http->withToken($this->apiKey);
        }

        $response = $http->post("{$this->baseUrl}/chat/completions", $payload);

        $elapsed = (hrtime(true) - $start) / 1_000_000;

        if (!$response->successful()) {
            $this->trackFailure($response->body());
            throw new \RuntimeException("Zen API error: {$response->body()}");
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
            Log::error('Zen analysis failed', ['error' => $e->getMessage()]);
            return AnalysisResult::fromArray(['error' => $e->getMessage()]);
        }
    }

    public function embed(string $text): array
    {
        throw new \RuntimeException('Zen does not support embeddings');
    }

    public function models(): array
    {
        return AIProvider::Zen->availableModels();
    }

    protected function checkHealth(): ProviderStatus
    {
        try {
            $http = Http::timeout(10);
            if ($this->apiKey) {
                $http = $http->withToken($this->apiKey);
            }

            $response = $http->post("{$this->baseUrl}/chat/completions", [
                'model' => 'big-pickle',
                'messages' => [['role' => 'user', 'content' => 'ping']],
                'max_tokens' => 2,
                'stream' => false,
            ]);

            if ($response->successful()) {
                return ProviderStatus::Active;
            }

            // 429 = rate limited but provider IS available (just busy)
            if ($response->status() === 429) {
                return ProviderStatus::Active;
            }

            return ProviderStatus::Unavailable;
        } catch (\Throwable $e) {
            return ProviderStatus::Unavailable;
        }
    }

    protected function getRateLimitPerMinute(): ?int
    {
        return 60;
    }

    protected function getRateLimitPerHour(): ?int
    {
        return 1000;
    }
}
