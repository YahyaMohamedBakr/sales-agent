<?php

namespace App\Domains\Agent\Services;

use App\Domains\Agent\Contracts\AIProviderInterface;
use App\Domains\Agent\Contracts\SmartRouterInterface;
use App\Domains\Agent\DTOs\AIResponse;
use App\Domains\Agent\DTOs\AnalysisResult;
use App\Domains\Agent\DTOs\ModelConfig;
use App\Domains\Agent\Enums\AIProvider;
use App\Domains\Agent\Enums\ModelTier;
use App\Domains\Agent\Enums\ProviderStatus;
use App\Domains\Agent\Enums\RouterStrategy;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SmartRouter implements SmartRouterInterface
{
    private const CACHE_KEY_LAST_FAILOVER = 'smart_router_last_failover';
    private const CACHE_KEY_PREFIX_HEALTH = 'provider_health_';

    /** @var array<string, AIProviderInterface> */
    private array $providers = [];

    private RouterStrategy $strategy;

    public function __construct(RouterStrategy $strategy = RouterStrategy::Smart)
    {
        $this->strategy = $strategy;
    }

    public function addProvider(AIProviderInterface $provider): void
    {
        $this->providers[$provider->provider()->value] = $provider;
    }

    public function removeProvider(AIProvider $provider): void
    {
        unset($this->providers[$provider->value]);
    }

    public function setStrategy(RouterStrategy $strategy): void
    {
        $this->strategy = $strategy;
    }

    public function chat(
        array $messages,
        ?AIProvider $preferred = null,
        RouterStrategy $strategy = RouterStrategy::Smart,
    ): AIResponse {
        $activeStrategy = $preferred !== null ? RouterStrategy::Priority : $strategy;
        $providers = $this->resolveProviders($preferred, $activeStrategy);

        $lastError = null;

        foreach ($providers as $providerName => $provider) {
            $config = $this->resolveConfig($provider, $activeStrategy);

            try {
                Log::debug('SmartRouter: trying provider', [
                    'provider' => $providerName,
                    'model' => $config->model,
                    'strategy' => $activeStrategy->value,
                ]);

                $response = $provider->chat($messages, $config);

                $this->recordSuccess($providerName);

                return $response;
            } catch (\Throwable $e) {
                $lastError = $e;
                $this->recordFailure($providerName, $e->getMessage());

                Log::warning('SmartRouter: provider failed, trying next', [
                    'provider' => $providerName,
                    'error' => $e->getMessage(),
                ]);

                continue;
            }
        }

        Log::error('SmartRouter: all providers failed', [
            'last_error' => $lastError?->getMessage(),
            'attempted' => array_keys($providers),
        ]);

        throw new \RuntimeException(
            'All AI providers failed. Last error: ' . ($lastError?->getMessage() ?? 'Unknown'),
        );
    }

    public function analyze(
        string $text,
        ?AIProvider $preferred = null,
        RouterStrategy $strategy = RouterStrategy::Smart,
    ): AnalysisResult {
        $providers = $this->resolveProviders($preferred, $strategy);

        foreach ($providers as $providerName => $provider) {
            $config = $this->resolveConfig($provider, $strategy);

            try {
                $result = $provider->analyze($text, $config);
                $this->recordSuccess($providerName);

                return $result;
            } catch (\Throwable $e) {
                $this->recordFailure($providerName, $e->getMessage());
                continue;
            }
        }

        return AnalysisResult::fromArray(['error' => 'All providers failed']);
    }

    public function availableProviders(): array
    {
        $available = [];

        foreach ($this->providers as $name => $provider) {
            $health = $provider->health();
            $available[$name] = [
                'provider' => $provider->provider(),
                'status' => $health->status,
                'models' => $provider->models(),
                'avg_latency' => $health->averageLatencyMs,
                'requests_minute' => $health->requestsThisMinute,
                'rate_limit_minute' => $health->rateLimitPerMinute,
            ];
        }

        return $available;
    }

    public function healthReport(): array
    {
        $report = [];

        foreach ($this->providers as $name => $provider) {
            $report[$name] = $provider->health();
        }

        return $report;
    }

    /**
     * @return array<string, AIProviderInterface>
     */
    private function resolveProviders(?AIProvider $preferred, RouterStrategy $strategy): array
    {
        if ($preferred !== null && isset($this->providers[$preferred->value])) {
            $health = $this->providers[$preferred->value]->health();

            if ($health->isAvailable()) {
                return [$preferred->value => $this->providers[$preferred->value]];
            }

            Log::info('SmartRouter: preferred provider unavailable, falling back', [
                'preferred' => $preferred->value,
                'status' => $health->status->value,
            ]);
        }

        return match ($strategy) {
            RouterStrategy::Smart => $this->getSmartOrder(),
            RouterStrategy::CostOptimized => $this->getCostOptimizedOrder(),
            RouterStrategy::Performance => $this->getPerformanceOrder(),
            RouterStrategy::Priority => $this->getPriorityOrder(),
        };
    }

    /**
     * الاستراتيجية الذكية:
     * 1. الأولوية للمدفوع اللي شغال (Premium)
     * 2. لو الـ Premium rate limited → Standard
     * 3. لو الكل fail → Free / Local
     */
    private function getSmartOrder(): array
    {
        $premium = [];
        $standard = [];
        $free = [];
        $local = [];

        foreach ($this->providers as $name => $provider) {
            $health = $provider->health();
            if (!$health->isAvailable()) continue;

            $tier = $provider->provider()->tier();

            match ($tier) {
                ModelTier::Premium => $premium[$name] = $provider,
                ModelTier::Standard => $standard[$name] = $provider,
                ModelTier::Free => $free[$name] = $provider,
                ModelTier::Local => $local[$name] = $provider,
            };
        }

        // ترتيب: Preium → Standard → Free → Local
        // وكل Tier مرتب حسب أقل latency
        $premium = $this->sortByLatency($premium);
        $standard = $this->sortByLatency($standard);
        $free = $this->sortByLatency($free);
        $local = $this->sortByLatency($local);

        // لو الـ Premium كلهم rate limited → نتخطى للـ Standard
        if ($this->allRateLimited($premium)) {
            Log::info('SmartRouter: premium tier exhausted, moving to standard');
            $premium = [];
        }

        return $premium + $standard + $free + $local;
    }

    private function getCostOptimizedOrder(): array
    {
        $ordered = [];

        // Local first (free)
        foreach ($this->providers as $name => $provider) {
            if ($provider->provider() === AIProvider::Ollama && $provider->isAvailable()) {
                $ordered[$name] = $provider;
            }
        }

        // Then free tier providers (Groq, Google free)
        foreach ($this->providers as $name => $provider) {
            if (!isset($ordered[$name]) && $provider->provider()->hasFreeTier() && $provider->isAvailable()) {
                $ordered[$name] = $provider;
            }
        }

        // Then standard
        foreach ($this->providers as $name => $provider) {
            if (!isset($ordered[$name]) && $provider->provider()->tier() === ModelTier::Standard && $provider->isAvailable()) {
                $ordered[$name] = $provider;
            }
        }

        // Finally premium
        foreach ($this->providers as $name => $provider) {
            if (!isset($ordered[$name]) && $provider->provider()->tier() === ModelTier::Premium && $provider->isAvailable()) {
                $ordered[$name] = $provider;
            }
        }

        return $ordered;
    }

    private function getPerformanceOrder(): array
    {
        $sorted = $this->providers;

        uasort($sorted, function (AIProviderInterface $a, AIProviderInterface $b) {
            $healthA = $a->health();
            $healthB = $b->health();

            if (!$healthA->isAvailable() && !$healthB->isAvailable()) return 0;
            if (!$healthA->isAvailable()) return 1;
            if (!$healthB->isAvailable()) return -1;

            $latA = $healthA->averageLatencyMs ?? PHP_FLOAT_MAX;
            $latB = $healthB->averageLatencyMs ?? PHP_FLOAT_MAX;

            return $latA <=> $latB;
        });

        return $sorted;
    }

    private function getPriorityOrder(): array
    {
        // Same as the order they were registered
        $available = [];
        foreach ($this->providers as $name => $provider) {
            if ($provider->isAvailable()) {
                $available[$name] = $provider;
            }
        }
        return $available;
    }

    private function resolveConfig(AIProviderInterface $provider, RouterStrategy $strategy): ModelConfig
    {
        $model = $provider->provider()->defaultModel();

        // Try to get user's preferred model for this provider from config
        $configModel = config("agent.models.{$provider->provider()->value}");
        if ($configModel) {
            $model = $configModel;
        }

        // In cost-optimized mode, use cheaper models
        if ($strategy === RouterStrategy::CostOptimized) {
            $model = match ($provider->provider()) {
                AIProvider::OpenAI => 'gpt-4o-mini',
                AIProvider::Anthropic => 'claude-3-haiku',
                AIProvider::Google => 'gemini-1.5-flash',
                AIProvider::Groq => 'llama-3.1-8b-instant',
                default => $model,
            };
        }

        return new ModelConfig(
            provider: $provider->provider(),
            model: $model,
            temperature: config('agent.temperature', 0.7),
            maxTokens: config('agent.max_tokens', 2048),
        );
    }

    private function sortByLatency(array $providers): array
    {
        uasort($providers, function (AIProviderInterface $a, AIProviderInterface $b) {
            $latA = $a->health()->averageLatencyMs ?? PHP_FLOAT_MAX;
            $latB = $b->health()->averageLatencyMs ?? PHP_FLOAT_MAX;
            return $latA <=> $latB;
        });

        return $providers;
    }

    private function allRateLimited(array $providers): bool
    {
        if (empty($providers)) return true;

        foreach ($providers as $provider) {
            if ($provider->health()->status !== ProviderStatus::RateLimited) {
                return false;
            }
        }

        return true;
    }

    private function recordSuccess(string $providerName): void
    {
        Cache::forget(static::CACHE_KEY_LAST_FAILOVER . "_{$providerName}");
    }

    private function recordFailure(string $providerName, string $error): void
    {
        Cache::put(
            static::CACHE_KEY_LAST_FAILOVER . "_{$providerName}",
            ['error' => $error, 'time' => now()],
            300,
        );
    }

    public function __debugInfo(): array
    {
        return [
            'strategy' => $this->strategy->value,
            'providers' => array_keys($this->providers),
            'available' => $this->availableProviders(),
        ];
    }
}
