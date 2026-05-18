<?php

namespace App\Domains\Agent\AIProviders;

use App\Domains\Agent\DTOs\AIResponse;
use App\Domains\Agent\DTOs\AnalysisResult;
use App\Domains\Agent\DTOs\ModelConfig;
use App\Domains\Agent\Enums\AIProvider;
use App\Domains\Agent\Enums\ProviderStatus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAIProvider extends BaseProvider
{
    private ?string $apiKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('agent.providers.openai.api_key') ?: env('OPENAI_API_KEY');
        $this->baseUrl = config('agent.providers.openai.base_url', 'https://api.openai.com/v1');
    }

    public function provider(): AIProvider
    {
        return AIProvider::OpenAI;
    }

    public function chat(array $messages, ModelConfig $config): AIResponse
    {
        $start = hrtime(true);

        $payload = $this->buildChatPayload($messages, $config);

        $response = Http::withToken($this->apiKey)
            ->timeout(30)
            ->post("{$this->baseUrl}/chat/completions", $payload);

        $elapsed = (hrtime(true) - $start) / 1_000_000;

        if (!$response->successful()) {
            $this->trackFailure($response->body());
            throw new \RuntimeException("OpenAI API error: {$response->body()}");
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
            Log::error('OpenAI analysis failed', ['error' => $e->getMessage()]);
            return AnalysisResult::fromArray(['error' => $e->getMessage()]);
        }
    }

    public function embed(string $text): array
    {
        $response = Http::withToken($this->apiKey)
            ->post("{$this->baseUrl}/embeddings", [
                'model' => 'text-embedding-3-small',
                'input' => $text,
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException("OpenAI Embedding error: {$response->body()}");
        }

        return $response->json()['data'][0]['embedding'] ?? [];
    }

    public function models(): array
    {
        return AIProvider::OpenAI->availableModels();
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

            if ($response->successful()) {
                return ProviderStatus::Active;
            }

            if ($response->status() === 429) {
                return ProviderStatus::RateLimited;
            }

            return ProviderStatus::Unavailable;
        } catch (\Throwable $e) {
            return ProviderStatus::Unavailable;
        }
    }

    protected function getRateLimitPerMinute(): ?int
    {
        return 500;
    }

    protected function getRateLimitPerHour(): ?int
    {
        return 10000;
    }
}
