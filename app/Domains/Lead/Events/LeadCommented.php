<?php

namespace App\Domains\Lead\Events;

use App\Domains\Lead\Models\Lead;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LeadCommented
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Lead $lead,
        public string $commentId,
        public string $commentText,
        public string $reply,
        public array $analysis = [],
    ) {}
}
