<?php

namespace App\Domains\Lead\Models;

use App\Domains\Campaign\Models\Campaign;
use App\Domains\Conversation\Models\Conversation;
use App\Domains\Agent\Models\AgentAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lead extends Model
{
    use HasUuids, SoftDeletes, HasFactory;

    protected $fillable = [
        'psid',
        'name',
        'phone',
        'email',
        'source',
        'campaign_id',
        'score',
        'status',
        'assigned_to',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'json',
            'score' => 'integer',
        ];
    }

    protected static function newFactory(): \Database\Factories\LeadFactory
    {
        return \Database\Factories\LeadFactory::new();
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function fieldValues(): HasMany
    {
        return $this->hasMany(LeadFieldValue::class);
    }

    public function agentActions(): HasMany
    {
        return $this->hasMany(AgentAction::class);
    }

    public function isQualified(): bool
    {
        return $this->score >= 70;
    }

    public function addScore(int $points): void
    {
        $this->increment('score', $points);
    }
}
