<?php

namespace App\Providers;

use App\Domains\Lead\Events\LeadCreated;
use App\Domains\Lead\Listeners\CalculateInitialScore;
use App\Domains\Lead\Listeners\CheckQualification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        LeadCreated::class => [
            CalculateInitialScore::class,
            CheckQualification::class,
        ],
    ];

    public function boot(): void
    {
        parent::boot();
    }
}
