<?php

namespace App\Domains\Agent\Models;

use App\Domains\Lead\Models\Lead;
use App\Domains\Conversation\Models\Conversation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentAction extends Model
{
    use HasUuids;

    protected $fillable = [
        'lead_id',
        'conversation_id',
        'agent_type',
        'action_type',
        'prompt',
        'response',
        'model_used',
        'tokens_used',
        'processing_time_ms',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'tokens_used' => 'integer',
            'processing_time_ms' => 'integer',
            'metadata' => 'json',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }
}
