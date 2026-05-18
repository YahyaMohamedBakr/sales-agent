<?php

return [
    App\Domains\Agent\Providers\AgentServiceProvider::class,
    App\Domains\Campaign\Providers\CampaignServiceProvider::class,
    App\Domains\Conversation\Providers\ConversationServiceProvider::class,
    App\Domains\KnowledgeBase\Providers\KnowledgeBaseServiceProvider::class,
    App\Domains\Lead\Providers\LeadServiceProvider::class,
    App\Providers\AppServiceProvider::class,
    App\Providers\EventServiceProvider::class,
    App\Providers\HorizonServiceProvider::class,
];
