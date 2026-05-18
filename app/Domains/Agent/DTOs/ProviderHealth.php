<?php

namespace App\Domains\Agent\DTOs;

use App\Domains\Agent\Enums\ProviderStatus;

class ProviderHealth
{
    public function __construct(
        public readonly string $provider,
        public readonly ProviderStatus $status,
        public readonly int $requestsThisMinute = 0,
        public readonly int $requestsThisHour = 0,
        public readonly ?int $rateLimitPerMinute = null,
        public readonly ?int $rateLimitPerHour = null,
        public readonly ?float $averageLatencyMs = null,
        public readonly ?\DateTimeImmutable $lastSuccessAt = null,
        public readonly ?\DateTimeImmutable $lastFailureAt = null,
        public readonly ?string $lastError = null,
    ) {}

    public function isAvailable(): bool
    {
        return $this->status === ProviderStatus::Active;
    }

    public function isRateLimited(): bool
    {
        return $this->status === ProviderStatus::RateLimited;
    }
}
