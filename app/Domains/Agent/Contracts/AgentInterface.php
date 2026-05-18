<?php

namespace App\Domains\Agent\Contracts;

use App\Domains\Agent\DTOs\AnalysisResult;
use App\Domains\Agent\DTOs\ModelConfig;

interface AgentInterface
{
    public function handle(string $message, array $context = []): string;

    public function shouldHandle(string $message, array $context = []): bool;

    public function analyze(string $message): AnalysisResult;

    public function name(): string;

    public function setModelConfig(ModelConfig $config): void;

    public function getModelConfig(): ModelConfig;
}
