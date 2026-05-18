<?php

namespace App\Domains\Agent\Providers;

use App\Domains\Agent\Agents\CommentReplyAgent;
use App\Domains\Agent\Agents\EmailAgent;
use App\Domains\Agent\Agents\LeadQualifierAgent;
use App\Domains\Agent\Agents\SupportAgent;
use App\Domains\Integration\Services\EmailService;
use App\Domains\Agent\AIProviders\AnthropicProvider;
use App\Domains\Agent\AIProviders\CohereProvider;
use App\Domains\Agent\AIProviders\DeepSeekProvider;
use App\Domains\Agent\AIProviders\GoogleProvider;
use App\Domains\Agent\AIProviders\GroqProvider;
use App\Domains\Agent\AIProviders\MistralProvider;
use App\Domains\Agent\AIProviders\OllamaProvider;
use App\Domains\Agent\AIProviders\OpenAIProvider;
use App\Domains\Agent\AIProviders\OpenRouterProvider;
use App\Domains\Agent\AIProviders\TogetherProvider;
use App\Domains\Agent\AIProviders\ZenProvider;
use App\Domains\Agent\Contracts\AIProviderInterface;
use App\Domains\Agent\Contracts\SmartRouterInterface;
use App\Domains\Agent\Enums\AIProvider as AIProviderEnum;
use App\Domains\Agent\Enums\RouterStrategy;
use App\Domains\Agent\Services\Orchestrator;
use App\Domains\Agent\Services\SmartRouter;
use App\Domains\Integration\Contracts\SocialPlatformInterface;
use App\Domains\Integration\Platforms\MetaPlatform;
use Illuminate\Support\ServiceProvider;

class AgentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../../../config/agent.php',
            'agent',
        );

        // Smart Router
        $this->app->singleton(SmartRouterInterface::class, function ($app) {
            $router = new SmartRouter(
                strategy: RouterStrategy::from(
                    config('agent.router_strategy', 'smart'),
                ),
            );

            return $this->registerProviders($router);
        });

        $this->app->alias(SmartRouterInterface::class, 'smart-router');

        // Social Platform
        $this->app->bind(SocialPlatformInterface::class, MetaPlatform::class);

        // Email Service
        $this->app->singleton(EmailService::class);

        // Orchestrator
        $this->app->singleton(Orchestrator::class, function ($app) {
            return new Orchestrator(
                leads: $app->make(\App\Domains\Lead\Repositories\LeadRepositoryInterface::class),
            );
        });
    }

    public function boot(): void
    {
        $this->registerAgents();
    }

    private function registerAgents(): void
    {
        $orchestrator = $this->app->make(Orchestrator::class);

        if (config('agent.agents.comment_reply.enabled', true)) {
            $orchestrator->registerAgent(
                $this->app->make(CommentReplyAgent::class),
            );
        }

        if (config('agent.agents.lead_qualifier.enabled', true)) {
            $orchestrator->registerAgent(
                $this->app->make(LeadQualifierAgent::class),
            );
        }

        if (config('agent.agents.email_agent.enabled', true)) {
            $orchestrator->registerAgent(
                $this->app->make(EmailAgent::class),
            );
        }

        if (config('agent.agents.support.enabled', true)) {
            $orchestrator->registerAgent(
                $this->app->make(SupportAgent::class),
            );
        }
    }

    private function registerProviders(SmartRouter $router): SmartRouter
    {
        $providerMap = [
            AIProviderEnum::OpenAI->value => OpenAIProvider::class,
            AIProviderEnum::Anthropic->value => AnthropicProvider::class,
            AIProviderEnum::Google->value => GoogleProvider::class,
            AIProviderEnum::Groq->value => GroqProvider::class,
            AIProviderEnum::Ollama->value => OllamaProvider::class,
            AIProviderEnum::OpenRouter->value => OpenRouterProvider::class,
            AIProviderEnum::Mistral->value => MistralProvider::class,
            AIProviderEnum::DeepSeek->value => DeepSeekProvider::class,
            AIProviderEnum::Together->value => TogetherProvider::class,
            AIProviderEnum::Cohere->value => CohereProvider::class,
            AIProviderEnum::Zen->value => ZenProvider::class,
        ];

        foreach ($providerMap as $name => $class) {
            try {
                $provider = $this->app->make($class);

                if ($provider->isAvailable()) {
                    $router->addProvider($provider);
                }
            } catch (\Throwable $e) {
                logger()->warning("Failed to register provider: {$name}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $router;
    }
}
