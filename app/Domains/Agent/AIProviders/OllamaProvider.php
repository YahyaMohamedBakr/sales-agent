<?php

namespace App\Domains\Agent\AIProviders;

use App\Domains\Agent\DTOs\AIResponse;
use App\Domains\Agent\DTOs\AnalysisResult;
use App\Domains\Agent\DTOs\ModelConfig;
use App\Domains\Agent\Enums\AIProvider;
use App\Domains\Agent\Enums\ProviderStatus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OllamaProvider extends BaseProvider
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('agent.providers.ollama.base_url', env('OLLAMA_URL', 'http://localhost:11434'));
    }

    public function provider(): AIProvider
    {
        return AIProvider::Ollama;
    }

    public function chat(array $messages, ModelConfig $config): AIResponse
    {
        $start = hrtime(true);

        $payload = [
            'model' => $config->model,
            'messages' => $messages,
            'stream' => false,
            'options' => [
                'temperature' => $config->temperature,
                'num_predict' => $config->maxTokens,
            ],
        ];

        $response = Http::timeout(60)
            ->post("{$this->baseUrl}/api/chat", $payload);

        $elapsed = (hrtime(true) - $start) / 1_000_000;

        if (!$response->successful()) {
            $this->trackFailure($response->body());
            throw new \RuntimeException("Ollama error: {$response->body()}");
        }

        $data = $response->json();
        $this->trackSuccess((int) $elapsed);

        return new AIResponse(
            content: $data['message']['content'] ?? '',
            model: $data['model'] ?? $config->model,
            provider: $this->provider()->value,
            inputTokens: $data['prompt_eval_count'] ?? 0,
            outputTokens: $data['eval_count'] ?? 0,
            processingTimeMs: (int) $elapsed,
        );
    }

    public function analyze(string $text, ModelConfig $config): AnalysisResult
    {
        $messages = $this->buildAnalysisPrompt($text);

        try {
            $response = $this->chat($messages, $config);
            return $this->parseAnalysisResponse($response->content);
        } catch (\Throwable $e) {
            Log::error('Ollama analysis failed', ['error' => $e->getMessage()]);
            return AnalysisResult::fromArray(['error' => $e->getMessage()]);
        }
    }

    public function embed(string $text): array
    {
        $response = Http::timeout(30)
            ->post("{$this->baseUrl}/api/embeddings", [
                'model' => 'nomic-embed-text',
                'prompt' => $text,
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException("Ollama Embedding error: {$response->body()}");
        }

        return $response->json()['embedding'] ?? [];
    }

    public function models(): array
    {
        try {
            $response = Http::timeout(5)->get("{$this->baseUrl}/api/tags");

            if (!$response->successful()) return AIProvider::Ollama->availableModels();

            $models = [];
            foreach ($response->json()['models'] ?? [] as $model) {
                $models[] = $model['name'];
            }

            return $models ?: AIProvider::Ollama->availableModels();
        } catch (\Throwable $e) {
            return AIProvider::Ollama->availableModels();
        }
    }

    protected function checkHealth(): ProviderStatus
    {
        try {
            $response = Http::timeout(3)->get("{$this->baseUrl}/api/tags");

            if ($response->successful()) return ProviderStatus::Active;

            return ProviderStatus::Unavailable;
        } catch (\Throwable $e) {
            return ProviderStatus::Unavailable;
        }
    }

    protected function getRateLimitPerMinute(): ?int
    {
        return null; // Local, no rate limit
    }

    protected function getRateLimitPerHour(): ?int
    {
        return null;
    }
}
