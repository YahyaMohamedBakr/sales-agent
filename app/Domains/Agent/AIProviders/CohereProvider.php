<?php

namespace App\Domains\Agent\AIProviders;

use App\Domains\Agent\DTOs\AIResponse;
use App\Domains\Agent\DTOs\AnalysisResult;
use App\Domains\Agent\DTOs\ModelConfig;
use App\Domains\Agent\Enums\AIProvider;
use App\Domains\Agent\Enums\ProviderStatus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CohereProvider extends BaseProvider
{
    private ?string $apiKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('agent.providers.cohere.api_key') ?: env('COHERE_API_KEY');
        $this->baseUrl = config('agent.providers.cohere.base_url', 'https://api.cohere.ai/v1');
    }

    public function provider(): AIProvider
    {
        return AIProvider::Cohere;
    }

    public function chat(array $messages, ModelConfig $config): AIResponse
    {
        $start = hrtime(true);

        $systemMsg = '';
        $chatHistory = [];
        $userMsg = '';

        foreach ($messages as $msg) {
            $role = $msg['role'] ?? '';
            $content = $msg['content'] ?? '';

            if ($role === 'system') {
                $systemMsg = $content;
            } elseif ($role === 'user') {
                $userMsg = $content;
            } elseif ($role === 'assistant') {
                $chatHistory[] = [
                    'role' => 'CHATBOT',
                    'message' => $content,
                ];
            }
        }

        $payload = [
            'model' => $config->model,
            'message' => $userMsg ?: end($messages)['content'] ?? '',
            'preamble_override' => $systemMsg ?: undefined,
            'temperature' => $config->temperature,
            'max_tokens' => $config->maxTokens,
        ];

        if (!empty($chatHistory)) {
            $payload['chat_history'] = $chatHistory;
        }

        $payload = array_filter($payload, fn ($v) => $v !== undefined);

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiKey}",
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])
            ->timeout(30)
            ->post("{$this->baseUrl}/chat", $payload);

        $elapsed = (hrtime(true) - $start) / 1_000_000;

        if (!$response->successful()) {
            $this->trackFailure($response->body());
            throw new \RuntimeException("Cohere API error: {$response->body()}");
        }

        $data = $response->json();
        $this->trackSuccess((int) $elapsed);

        return new AIResponse(
            content: $data['text'] ?? '',
            model: $data['model'] ?? $config->model,
            provider: $this->provider()->value,
            inputTokens: $data['meta']['billed_units']['input_tokens'] ?? 0,
            outputTokens: $data['meta']['billed_units']['output_tokens'] ?? 0,
            processingTimeMs: (int) $elapsed,
            finishReason: $data['finish_reason'] ?? null,
        );
    }

    public function analyze(string $text, ModelConfig $config): AnalysisResult
    {
        $messages = $this->buildAnalysisPrompt($text);

        try {
            $response = $this->chat($messages, $config);
            return $this->parseAnalysisResponse($response->content);
        } catch (\Throwable $e) {
            Log::error('Cohere analysis failed', ['error' => $e->getMessage()]);
            return AnalysisResult::fromArray(['error' => $e->getMessage()]);
        }
    }

    public function embed(string $text): array
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiKey}",
            'Content-Type' => 'application/json',
        ])->post("{$this->baseUrl}/embed", [
            'model' => 'embed-english-v3.0',
            'texts' => [$text],
            'input_type' => 'search_document',
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException("Cohere Embedding error: {$response->body()}");
        }

        return $response->json()['embeddings'][0] ?? [];
    }

    public function models(): array
    {
        return AIProvider::Cohere->availableModels();
    }

    protected function checkHealth(): ProviderStatus
    {
        if (!$this->apiKey) {
            return ProviderStatus::NotConfigured;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Accept' => 'application/json',
            ])
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
        return 100;
    }

    protected function getRateLimitPerHour(): ?int
    {
        return 5000;
    }
}
