<?php

namespace App\Domains\Agent\AIProviders;

use App\Domains\Agent\DTOs\AIResponse;
use App\Domains\Agent\DTOs\AnalysisResult;
use App\Domains\Agent\DTOs\ModelConfig;
use App\Domains\Agent\Enums\AIProvider;
use App\Domains\Agent\Enums\ProviderStatus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AnthropicProvider extends BaseProvider
{
    private ?string $apiKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('agent.providers.anthropic.api_key') ?: env('ANTHROPIC_API_KEY');
        $this->baseUrl = config('agent.providers.anthropic.base_url', 'https://api.anthropic.com/v1');
    }

    public function provider(): AIProvider
    {
        return AIProvider::Anthropic;
    }

    public function chat(array $messages, ModelConfig $config): AIResponse
    {
        $start = hrtime(true);

        $systemMsg = null;
        $chatMessages = $messages;

        if (isset($messages[0]) && $messages[0]['role'] === 'system') {
            $systemMsg = $messages[0]['content'];
            $chatMessages = array_slice($messages, 1);
        }

        $payload = [
            'model' => $config->model,
            'messages' => $chatMessages,
            'max_tokens' => $config->maxTokens,
            'temperature' => $config->temperature,
        ];

        if ($systemMsg) {
            $payload['system'] = $systemMsg;
        }

        $response = Http::withHeaders([
            'x-api-key' => $this->apiKey,
            'anthropic-version' => '2023-06-01',
            'Content-Type' => 'application/json',
        ])->timeout(30)->post("{$this->baseUrl}/messages", $payload);

        $elapsed = (hrtime(true) - $start) / 1_000_000;

        if (!$response->successful()) {
            $this->trackFailure($response->body());
            throw new \RuntimeException("Anthropic API error: {$response->body()}");
        }

        $data = $response->json();
        $this->trackSuccess((int) $elapsed);

        $content = '';
        foreach ($data['content'] ?? [] as $block) {
            if (($block['type'] ?? '') === 'text') {
                $content .= $block['text'];
            }
        }

        return new AIResponse(
            content: $content,
            model: $data['model'] ?? $config->model,
            provider: $this->provider()->value,
            inputTokens: $data['usage']['input_tokens'] ?? 0,
            outputTokens: $data['usage']['output_tokens'] ?? 0,
            processingTimeMs: (int) $elapsed,
            finishReason: $data['stop_reason'] ?? null,
        );
    }

    public function analyze(string $text, ModelConfig $config): AnalysisResult
    {
        $messages = $this->buildAnalysisPrompt($text);

        try {
            $response = $this->chat($messages, $config);
            return $this->parseAnalysisResponse($response->content);
        } catch (\Throwable $e) {
            Log::error('Anthropic analysis failed', ['error' => $e->getMessage()]);
            return AnalysisResult::fromArray(['error' => $e->getMessage()]);
        }
    }

    public function embed(string $text): array
    {
        throw new \RuntimeException('Anthropic does not support embeddings directly');
    }

    public function models(): array
    {
        return AIProvider::Anthropic->availableModels();
    }

    protected function checkHealth(): ProviderStatus
    {
        if (!$this->apiKey) {
            return ProviderStatus::NotConfigured;
        }

        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01',
            ])->timeout(5)->get("{$this->baseUrl}/models");

            if ($response->successful()) {
                return ProviderStatus::Active;
            }

            return $response->status() === 429
                ? ProviderStatus::RateLimited
                : ProviderStatus::Unavailable;
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
