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
    private ?string $apiKey;

    public function __construct()
    {
        $this->baseUrl = config('agent.providers.ollama.base_url', env('OLLAMA_URL', 'http://localhost:11434'));
        $this->apiKey = config('agent.providers.ollama.api_key') ?: env('OLLAMA_API_KEY');
    }

    public function provider(): AIProvider
    {
        return AIProvider::Ollama;
    }

    public function chat(array $messages, ModelConfig $config): AIResponse
    {
        $start = hrtime(true);

        // Use OpenAI-compatible API if key is set (cloud Ollama)
        if ($this->apiKey) {
            return $this->chatViaOpenAI($messages, $config, $start);
        }

        return $this->chatViaNative($messages, $config, $start);
    }

    private function chatViaOpenAI(array $messages, ModelConfig $config, int $start): AIResponse
    {
        $payload = $this->buildChatPayload($messages, $config);

        $response = Http::withToken($this->apiKey)
            ->timeout(60)
            ->post("{$this->baseUrl}/v1/chat/completions", $payload);

        $elapsed = (hrtime(true) - $start) / 1_000_000;

        if (!$response->successful()) {
            $this->trackFailure($response->body());
            throw new \RuntimeException("Ollama (OpenAI) error: {$response->body()}");
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

    private function chatViaNative(array $messages, ModelConfig $config, int $start): AIResponse
    {
        $payload = [
            'model' => $config->model,
            'messages' => $messages,
            'stream' => false,
            'options' => [
                'temperature' => $config->temperature,
                'num_predict' => $config->maxTokens,
            ],
        ];

        $request = Http::timeout(60);

        if ($this->apiKey) {
            $request->withToken($this->apiKey);
        }

        $response = $request->post("{$this->baseUrl}/api/chat", $payload);

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
        $request = Http::timeout(30);

        if ($this->apiKey) {
            $request->withToken($this->apiKey);
        }

        $response = $request->post("{$this->baseUrl}/api/embeddings", [
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
            $request = Http::timeout(5);

            if ($this->apiKey) {
                $request->withToken($this->apiKey);
            }

            $response = $request->get("{$this->baseUrl}/api/tags");

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
        if (!$this->baseUrl || $this->baseUrl === 'http://localhost:11434') {
            return ProviderStatus::NotConfigured;
        }

        try {
            $request = Http::timeout(5);

            if ($this->apiKey) {
                $request->withToken($this->apiKey);
            }

            $response = $request->get("{$this->baseUrl}/api/tags");

            if ($response->successful()) return ProviderStatus::Active;
            if ($response->status() === 429) return ProviderStatus::RateLimited;

            return ProviderStatus::Unavailable;
        } catch (\Throwable $e) {
            return ProviderStatus::Unavailable;
        }
    }

    protected function getRateLimitPerMinute(): ?int
    {
        return $this->apiKey ? 60 : null;
    }

    protected function getRateLimitPerHour(): ?int
    {
        return $this->apiKey ? 5000 : null;
    }
}
