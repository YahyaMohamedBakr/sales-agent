<?php

namespace App\Jobs;

use App\Domains\Agent\Contracts\SmartRouterInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MonitorProviderHealth implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function handle(SmartRouterInterface $router): void
    {
        $report = $router->healthReport();

        Cache::put('agent:health_report', $report, 300);

        $offline = array_filter($report, fn ($p) => ($p['status'] ?? '') === 'offline');

        if (!empty($offline)) {
            Log::warning('AI provider(s) offline', [
                'providers' => array_keys($offline),
            ]);
        }

        Log::info('Provider health check completed', [
            'total' => count($report),
            'online' => count(array_filter($report, fn ($p) => ($p['status'] ?? '') === 'online')),
            'offline' => count($offline),
        ]);
    }
}
