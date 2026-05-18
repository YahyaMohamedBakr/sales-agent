<?php

namespace App\Domains\Agent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class AgentConfig extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'agent_type',
        'name',
        'config',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'config' => 'json',
            'active' => 'boolean',
        ];
    }
}
