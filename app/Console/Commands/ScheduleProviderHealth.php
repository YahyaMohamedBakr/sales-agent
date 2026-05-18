<?php

namespace App\Console\Commands;

use App\Jobs\MonitorProviderHealth;
use Illuminate\Console\Command;

class ScheduleProviderHealth extends Command
{
    protected $signature = 'agents:health-check';
    protected $description = 'Check AI provider health and cache results';

    public function handle(): void
    {
        MonitorProviderHealth::dispatch();
        $this->info('Provider health check dispatched to queue.');
    }
}
