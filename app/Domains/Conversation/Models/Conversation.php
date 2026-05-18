<?php

namespace App\Domains\Conversation\Models;

use App\Domains\Lead\Models\Lead;
use App\Domains\Agent\Models\AgentAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    use HasUuids, SoftDeletes, HasFactory;

    protected $fillable = [
        'lead_id',
        'channel',
        'message',
        'direction',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'json',
        ];
    }

    protected static function newFactory(): \Database\Factories\ConversationFactory
    {
        return \Database\Factories\ConversationFactory::new();
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function agentActions(): HasMany
    {
        return $this->hasMany(AgentAction::class);
    }
}
