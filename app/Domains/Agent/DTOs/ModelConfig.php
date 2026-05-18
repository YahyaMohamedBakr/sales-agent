<?php

namespace App\Domains\Agent\DTOs;

use App\Domains\Agent\Enums\AIProvider;
use App\Domains\Agent\Enums\ModelTier;

class ModelConfig
{
    public function __construct(
        public readonly AIProvider $provider,
        public readonly string $model,
        public readonly float $temperature = 0.7,
        public readonly int $maxTokens = 2048,
        public readonly ?float $topP = null,
        public readonly ?array $stopSequences = null,
        public readonly ModelTier $tier = ModelTier::Standard,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            provider: $data['provider'] instanceof AIProvider
                ? $data['provider']
                : AIProvider::from($data['provider']),
            model: $data['model'],
            temperature: $data['temperature'] ?? 0.7,
            maxTokens: $data['max_tokens'] ?? 2048,
            topP: $data['top_p'] ?? null,
            stopSequences: $data['stop'] ?? null,
            tier: isset($data['tier'])
                ? ($data['tier'] instanceof ModelTier ? $data['tier'] : ModelTier::from($data['tier']))
                : ModelTier::Standard,
        );
    }

    public function toArray(): array
    {
        return [
            'provider' => $this->provider->value,
            'model' => $this->model,
            'temperature' => $this->temperature,
            'max_tokens' => $this->maxTokens,
            'top_p' => $this->topP,
            'stop' => $this->stopSequences,
            'tier' => $this->tier->value,
        ];
    }

    public function identifier(): string
    {
        return "{$this->provider->value}:{$this->model}";
    }
}
