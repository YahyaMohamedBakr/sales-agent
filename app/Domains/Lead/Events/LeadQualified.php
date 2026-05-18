<?php

namespace App\Domains\Lead\Events;

use App\Domains\Lead\Models\Lead;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LeadQualified
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Lead $lead,
        public int $score,
        public array $criteria = [],
    ) {}
}
