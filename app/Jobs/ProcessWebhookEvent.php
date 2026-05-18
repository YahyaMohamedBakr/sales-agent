<?php

namespace App\Jobs;

use App\Domains\Agent\Services\Orchestrator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessWebhookEvent implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function __construct(
        private array $event,
        private string $source,
    ) {
        $this->onQueue('meta-webhooks');
    }

    public function handle(Orchestrator $orchestrator): void
    {
        try {
            match ($this->event['type']) {
                'comment' => $orchestrator->handleComment($this->event),
                'message' => $orchestrator->handleMessage($this->event),
                'postback' => $orchestrator->handlePostback($this->event),
                default => Log::debug('Unknown webhook event type', $this->event),
            };
        } catch (\Throwable $e) {
            Log::error('Failed to process webhook event', [
                'type' => $this->event['type'] ?? 'unknown',
                'source' => $this->source,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
