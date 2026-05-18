<?php

namespace App\Domains\Lead\Listeners;

use App\Domains\Lead\Enums\LeadScoreEvent;
use App\Domains\Lead\Events\LeadCreated;
use App\Domains\Lead\Events\LeadQualified;
use Illuminate\Support\Facades\Log;

class CheckQualification
{
    public function handle(LeadCreated $event): void
    {
        $lead = $event->lead;

        if ($lead->score >= LeadScoreEvent::threshold() && $lead->status !== 'qualified') {
            $lead->update(['status' => 'qualified']);

            LeadQualified::dispatch($lead, $lead->score);

            Log::info('Lead qualified', [
                'lead_id' => $lead->id,
                'score' => $lead->score,
            ]);
        }
    }
}
