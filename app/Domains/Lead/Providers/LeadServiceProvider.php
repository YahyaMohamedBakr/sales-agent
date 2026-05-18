<?php

namespace App\Domains\Lead\Providers;

use App\Domains\Lead\Listeners\CalculateInitialScore;
use App\Domains\Lead\Events\LeadCreated;
use App\Domains\Lead\Repositories\LeadRepository;
use App\Domains\Lead\Repositories\LeadRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class LeadServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(LeadRepositoryInterface::class, LeadRepository::class);
    }

    public function boot(): void
    {
        //
    }
}
