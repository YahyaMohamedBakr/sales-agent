<?php

namespace App\Domains\Conversation\Repositories;

use App\Domains\Conversation\Models\Conversation;
use App\Support\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

interface ConversationRepositoryInterface extends BaseRepositoryInterface
{
    public function findByLead(string $leadId): Collection;

    public function findByChannel(string $channel): Collection;

    public function findRecentByLead(string $leadId, int $limit = 20): Collection;

    public function logMessage(string $leadId, string $channel, string $message, string $direction, array $metadata = []): Conversation;
}
