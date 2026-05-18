<?php

namespace App\Domains\Campaign\Providers;

use App\Domains\Campaign\Repositories\CampaignRepository;
use App\Domains\Campaign\Repositories\CampaignRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class CampaignServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CampaignRepositoryInterface::class, CampaignRepository::class);
    }

    public function boot(): void
    {
        //
    }
}
