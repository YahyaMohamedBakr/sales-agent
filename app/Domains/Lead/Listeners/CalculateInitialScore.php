<?php

namespace App\Domains\Lead\Listeners;

use App\Domains\Lead\Events\LeadCreated;
use App\Domains\Lead\Enums\LeadScoreEvent;
use Illuminate\Support\Facades\Log;

class CalculateInitialScore
{
    public function handle(LeadCreated $event): void
    {
        $score = 0;

        if ($event->lead->phone) {
            $score += LeadScoreEvent::PhoneShared->points();
        }

        if ($event->lead->email) {
            $score += LeadScoreEvent::EmailShared->points();
        }

        if ($event->message) {
            $score += LeadScoreEvent::Interacted->points();
        }

        $event->lead->update(['score' => $score]);

        Log::debug('Lead initial score calculated', [
            'lead_id' => $event->lead->id,
            'score' => $score,
            'source' => $event->source,
        ]);
    }
}
