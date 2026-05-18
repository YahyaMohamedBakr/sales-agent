<?php

namespace App\Domains\Agent\DTOs;

class AIResponse
{
    public function __construct(
        public readonly string $content,
        public readonly string $model,
        public readonly string $provider,
        public readonly int $inputTokens = 0,
        public readonly int $outputTokens = 0,
        public readonly int $processingTimeMs = 0,
        public readonly ?string $finishReason = null,
    ) {}

    public function totalTokens(): int
    {
        return $this->inputTokens + $this->outputTokens;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            content: $data['content'],
            model: $data['model'],
            provider: $data['provider'],
            inputTokens: $data['input_tokens'] ?? 0,
            outputTokens: $data['output_tokens'] ?? 0,
            processingTimeMs: $data['processing_time_ms'] ?? 0,
            finishReason: $data['finish_reason'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'content' => $this->content,
            'model' => $this->model,
            'provider' => $this->provider,
            'input_tokens' => $this->inputTokens,
            'output_tokens' => $this->outputTokens,
            'processing_time_ms' => $this->processingTimeMs,
            'finish_reason' => $this->finishReason,
        ];
    }
}
