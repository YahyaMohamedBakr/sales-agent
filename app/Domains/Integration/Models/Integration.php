<?php

namespace App\Domains\Integration\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Integration extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'platform',
        'credentials',
        'webhook_secret',
        'active',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'credentials' => 'encrypted',
            'webhook_secret' => 'encrypted',
            'active' => 'boolean',
            'metadata' => 'json',
        ];
    }
}
