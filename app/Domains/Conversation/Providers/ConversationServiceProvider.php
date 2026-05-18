<?php

namespace App\Domains\Conversation\Providers;

use App\Domains\Conversation\Repositories\ConversationRepository;
use App\Domains\Conversation\Repositories\ConversationRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class ConversationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ConversationRepositoryInterface::class, ConversationRepository::class);
    }

    public function boot(): void
    {
        //
    }
}
