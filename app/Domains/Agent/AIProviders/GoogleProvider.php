<?php

namespace App\Domains\Agent\AIProviders;

use App\Domains\Agent\DTOs\AIResponse;
use App\Domains\Agent\DTOs\AnalysisResult;
use App\Domains\Agent\DTOs\ModelConfig;
use App\Domains\Agent\Enums\AIProvider;
use App\Domains\Agent\Enums\ProviderStatus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleProvider extends BaseProvider
{
    private ?string $apiKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('agent.providers.google.api_key') ?: env('GOOGLE_API_KEY');
        $this->baseUrl = config('agent.providers.google.base_url', 'https://generativelanguage.googleapis.com/v1beta');
    }

    public function provider(): AIProvider
    {
        return AIProvider::Google;
    }

    public function chat(array $messages, ModelConfig $config): AIResponse
    {
        $start = hrtime(true);

        $geminiMessages = $this->convertToGeminiFormat($messages);

        $payload = [
            'contents' => $geminiMessages,
            'generationConfig' => [
                'temperature' => $config->temperature,
                'maxOutputTokens' => $config->maxTokens,
            ],
        ];

        $url = "{$this->baseUrl}/models/{$config->model}:generateContent?key={$this->apiKey}";

        $response = Http::timeout(30)->post($url, $payload);

        $elapsed = (hrtime(true) - $start) / 1_000_000;

        if (!$response->successful()) {
            $this->trackFailure($response->body());
            throw new \RuntimeException("Google AI error: {$response->body()}");
        }

        $data = $response->json();
        $this->trackSuccess((int) $elapsed);

        $content = '';
        foreach ($data['candidates'][0]['content']['parts'] ?? [] as $part) {
            $content .= $part['text'] ?? '';
        }

        return new AIResponse(
            content: $content,
            model: $config->model,
            provider: $this->provider()->value,
            inputTokens: $data['usageMetadata']['promptTokenCount'] ?? 0,
            outputTokens: $data['usageMetadata']['candidatesTokenCount'] ?? 0,
            processingTimeMs: (int) $elapsed,
            finishReason: $data['candidates'][0]['finishReason'] ?? null,
        );
    }

    public function analyze(string $text, ModelConfig $config): AnalysisResult
    {
        $messages = $this->buildAnalysisPrompt($text);

        try {
            $response = $this->chat($messages, $config);
            return $this->parseAnalysisResponse($response->content);
        } catch (\Throwable $e) {
            Log::error('Google analysis failed', ['error' => $e->getMessage()]);
            return AnalysisResult::fromArray(['error' => $e->getMessage()]);
        }
    }

    public function embed(string $text): array
    {
        $url = "{$this->baseUrl}/models/text-embedding-004:embedContent?key={$this->apiKey}";

        $response = Http::timeout(30)->post($url, [
            'model' => 'models/text-embedding-004',
            'content' => ['parts' => [['text' => $text]]],
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException("Google Embedding error: {$response->body()}");
        }

        return $response->json()['embedding']['values'] ?? [];
    }

    public function models(): array
    {
        return AIProvider::Google->availableModels();
    }

    protected function checkHealth(): ProviderStatus
    {
        if (!$this->apiKey) {
            return ProviderStatus::NotConfigured;
        }

        try {
            $url = "{$this->baseUrl}/models?key={$this->apiKey}";
            $response = Http::timeout(5)->get($url);

            if ($response->successful()) return ProviderStatus::Active;
            if ($response->status() === 429) return ProviderStatus::RateLimited;

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

    private function convertToGeminiFormat(array $messages): array
    {
        $geminiMessages = [];
        foreach ($messages as $msg) {
            if ($msg['role'] === 'system') continue;

            $role = $msg['role'] === 'assistant' ? 'model' : 'user';
            $geminiMessages[] = [
                'role' => $role,
                'parts' => [['text' => $msg['content']]],
            ];
        }
        return $geminiMessages;
    }
}
