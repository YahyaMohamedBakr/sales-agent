<?php

namespace App\Providers;

use App\Domains\Setting\Services\SettingsService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SettingsService::class);
    }

    public function boot(): void
    {
        try {
            $this->app->make(SettingsService::class)->mergeIntoLaravelConfig();
        } catch (\Throwable $e) {
            // Settings table may not exist yet (fresh install)
        }
    }
}
