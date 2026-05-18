<?php

namespace App\Console\Commands;

use App\Domains\Agent\AIProviders\AnthropicProvider;
use App\Domains\Agent\AIProviders\GoogleProvider;
use App\Domains\Agent\AIProviders\GroqProvider;
use App\Domains\Agent\AIProviders\OllamaProvider;
use App\Domains\Agent\AIProviders\OpenAIProvider;
use App\Domains\Agent\AIProviders\OpenRouterProvider;
use App\Domains\Agent\Contracts\SmartRouterInterface;
use App\Domains\Agent\DTOs\ModelConfig;
use App\Domains\Agent\Enums\AIProvider;
use App\Domains\Agent\Enums\RouterStrategy;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestLLMProviders extends Command
{
    protected $signature = 'agent:test-providers
        {--provider=all : Provider name (openai, anthropic, google, groq, ollama, openrouter) or all}
        {--strategy=smart : Router strategy (smart, priority, cost_optimized, performance)}
        {--message= : Custom message to send}
        {--health : Show health report only}';

    protected $description = 'Test LLM provider connections and the Smart Router';

    public function handle(SmartRouterInterface $router): int
    {
        $message = $this->option('message') ?? 'مرحبا! كم سعر المنتج؟';
        $providerName = $this->option('provider');
        $showHealth = $this->option('health');

        $this->newLine();
        $this->info('🤖 AI Sales Agent — LLM Provider Test');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        if ($showHealth) {
            return $this->showHealthReport($router);
        }

        if ($providerName === 'all') {
            return $this->testAllProviders($router, $message);
        }

        return $this->testSingleProvider($router, $providerName, $message);
    }

    private function testAllProviders(SmartRouterInterface $router, string $message): int
    {
        $this->info("\n📋 Testing Smart Router with strategy: {$this->option('strategy')}");
        $this->line('────────────────────────────────────────');

        $strategy = RouterStrategy::from($this->option('strategy'));
        $now = now();

        try {
            $response = $router->chat(
                messages: [
                    [
                        'role' => 'system',
                        'content' => 'You are a sales assistant. Reply in Arabic. Be brief.',
                    ],
                    ['role' => 'user', 'content' => $message],
                ],
                strategy: $strategy,
            );

            $elapsed = now()->diffInMilliseconds($now);

            $this->line("  Provider:  <fg=green>{$response->provider}</>");
            $this->line("  Model:     <fg=yellow>{$response->model}</>");
            $this->line("  Tokens:    {$response->inputTokens} in / {$response->outputTokens} out");
            $this->line("  Time:      {$elapsed}ms");
            $this->line("  Response:  {$response->content}");

        } catch (\Throwable $e) {
            $this->error("  All providers failed: {$e->getMessage()}");
            return self::FAILURE;
        }

        $this->showHealthReport($router);
        return self::SUCCESS;
    }

    private function testSingleProvider(SmartRouterInterface $router, string $providerName, string $message): int
    {
        $provider = AIProvider::tryFrom($providerName);

        if (!$provider) {
            $this->error("Unknown provider: {$providerName}");
            $this->line('Available: ' . implode(', ', array_keys($router->availableProviders())));
            return self::FAILURE;
        }

        $this->info("\n🔍 Testing provider: {$provider->label()}");
        $this->line('────────────────────────────────────────');

        $now = now();

        try {
            $response = $router->chat(
                messages: [
                    ['role' => 'user', 'content' => $message],
                ],
                preferred: $provider,
            );

            $elapsed = now()->diffInMilliseconds($now);

            $this->line("  Status:    <fg=green>✅ Success</>");
            $this->line("  Model:     <fg=yellow>{$response->model}</>");
            $this->line("  Tokens:    {$response->inputTokens} in / {$response->outputTokens} out");
            $this->line("  Time:      {$elapsed}ms");
            $this->line("  Response:  {$response->content}");

        } catch (\Throwable $e) {
            $elapsed = now()->diffInMilliseconds($now);
            $this->line("  Status:    <fg=red>❌ Failed</>");
            $this->line("  Error:     {$e->getMessage()}");
            $this->line("  Time:      {$elapsed}ms");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function showHealthReport(SmartRouterInterface $router): int
    {
        $this->info("\n📊 Provider Health Report");
        $this->line('────────────────────────');

        $report = $router->healthReport();

        if (empty($report)) {
            $this->warn('  No providers registered. Check your .env configuration.');
            return self::FAILURE;
        }

        $headers = ['Provider', 'Status', 'Models', 'Latency', 'Rate (min)', 'Last Error'];
        $rows = [];

        foreach ($report as $name => $health) {
            $statusTag = match ($health->status->value) {
                'active' => '<fg=green>● Active</>',
                'not_configured' => '<fg=yellow>● No API Key</>',
                'rate_limited' => '<fg=red>● Rate Limited</>',
                'unavailable' => '<fg=red>● Unavailable</>',
                default => "<fg=gray>● {$health->status->value}</>",
            };

            $rows[] = [
                $name,
                $statusTag,
                count(AIProvider::tryFrom($name)?->availableModels() ?? []),
                $health->averageLatencyMs ? "{$health->averageLatencyMs}ms" : '-',
                $health->rateLimitPerMinute
                    ? "{$health->requestsThisMinute}/{$health->rateLimitPerMinute}"
                    : 'unlimited',
                $health->lastError ?? '-',
            ];
        }

        $this->table($headers, $rows);

        $available = count(array_filter($report, fn($h) => $h->isAvailable()));
        $this->line("\n  <fg=green>{$available}</> of <fg=yellow>" . count($report) . '</> providers available');

        return self::SUCCESS;
    }
}
