<?php

namespace App\Domains\Conversation\Repositories;

use App\Domains\Conversation\Models\Conversation;
use App\Support\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;

class ConversationRepository extends BaseRepository implements ConversationRepositoryInterface
{
    public function __construct(Conversation $model)
    {
        parent::__construct($model);
    }

    public function findByLead(string $leadId): Collection
    {
        return $this->query()
            ->where('lead_id', $leadId)
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function findByChannel(string $channel): Collection
    {
        return $this->query()
            ->where('channel', $channel)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function findRecentByLead(string $leadId, int $limit = 20): Collection
    {
        return $this->query()
            ->where('lead_id', $leadId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->reverse();
    }

    public function logMessage(string $leadId, string $channel, string $message, string $direction, array $metadata = []): Conversation
    {
        return $this->create([
            'lead_id' => $leadId,
            'channel' => $channel,
            'message' => $message,
            'direction' => $direction,
            'metadata' => $metadata,
        ]);
    }
}
