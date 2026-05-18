<?php

namespace App\Jobs;

use App\Domains\Agent\Contracts\SmartRouterInterface;
use App\Domains\Agent\DTOs\AIResponse;
use App\Domains\Agent\Models\AgentAction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;

class ProcessLLMRequest implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function __construct(
        private array $messages,
        private string $actionType,
        private ?string $leadId = null,
        private ?string $conversationId = null,
        private ?string $preferredProvider = null,
    ) {
        $this->onQueue('llm-calls');
    }

    public function handle(SmartRouterInterface $router): void
    {
        $start = microtime(true);

        try {
            $response = $router->chat(
                messages: $this->messages,
                preferred: $this->preferredProvider,
            );

            AgentAction::create([
                'lead_id' => $this->leadId,
                'conversation_id' => $this->conversationId,
                'action_type' => $this->actionType,
                'prompt' => $this->messages[count($this->messages) - 1]['content'] ?? '',
                'response' => $response->content,
                'model_used' => $response->model,
                'provider' => $response->provider,
                'tokens_used' => $response->totalTokens(),
                'processing_time_ms' => (int) ((microtime(true) - $start) * 1000),
            ]);
        } catch (\Throwable $e) {
            AgentAction::create([
                'lead_id' => $this->leadId,
                'conversation_id' => $this->conversationId,
                'action_type' => $this->actionType,
                'prompt' => $this->messages[count($this->messages) - 1]['content'] ?? '',
                'response' => "Error: {$e->getMessage()}",
                'model_used' => 'error',
                'tokens_used' => 0,
                'processing_time_ms' => (int) ((microtime(true) - $start) * 1000),
            ]);

            throw $e;
        }
    }
}
