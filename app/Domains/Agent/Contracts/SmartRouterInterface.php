<?php

namespace App\Domains\Agent\Contracts;

use App\Domains\Agent\DTOs\AIResponse;
use App\Domains\Agent\DTOs\AnalysisResult;
use App\Domains\Agent\Enums\AIProvider;
use App\Domains\Agent\Enums\RouterStrategy;

interface SmartRouterInterface
{
    public function chat(array $messages, ?AIProvider $preferred = null, RouterStrategy $strategy = RouterStrategy::Smart): AIResponse;

    public function analyze(string $text, ?AIProvider $preferred = null, RouterStrategy $strategy = RouterStrategy::Smart): AnalysisResult;

    public function addProvider(AIProviderInterface $provider): void;

    public function removeProvider(AIProvider $provider): void;

    public function setStrategy(RouterStrategy $strategy): void;

    public function availableProviders(): array;

    public function healthReport(): array;
}
