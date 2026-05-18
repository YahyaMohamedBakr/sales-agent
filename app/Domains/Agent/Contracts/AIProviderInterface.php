<?php

namespace App\Domains\Agent\Contracts;

use App\Domains\Agent\DTOs\AIResponse;
use App\Domains\Agent\DTOs\AnalysisResult;
use App\Domains\Agent\DTOs\ModelConfig;
use App\Domains\Agent\DTOs\ProviderHealth;
use App\Domains\Agent\Enums\AIProvider;

interface AIProviderInterface
{
    public function provider(): AIProvider;

    public function chat(array $messages, ModelConfig $config): AIResponse;

    public function analyze(string $text, ModelConfig $config): AnalysisResult;

    public function embed(string $text): array;

    public function health(): ProviderHealth;

    public function isAvailable(): bool;

    public function models(): array;
}
