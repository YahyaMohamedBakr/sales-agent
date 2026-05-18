<?php

namespace App\Domains\Conversation\Events;

use App\Domains\Lead\Models\Lead;
use App\Domains\Conversation\Models\Conversation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageReceived
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Lead $lead,
        public Conversation $conversation,
        public string $channel,
        public string $message,
    ) {}
}
