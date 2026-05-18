<?php

namespace App\Domains\KnowledgeBase\Providers;

use App\Domains\KnowledgeBase\Repositories\KnowledgeBaseRepository;
use App\Domains\KnowledgeBase\Repositories\KnowledgeBaseRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class KnowledgeBaseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(KnowledgeBaseRepositoryInterface::class, KnowledgeBaseRepository::class);
    }

    public function boot(): void
    {
        //
    }
}
