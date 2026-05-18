<?php

namespace App\Domains\Campaign\Models;

use App\Domains\Lead\Models\Lead;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    use HasUuids, SoftDeletes, HasFactory;

    protected $fillable = [
        'name',
        'meta_ad_id',
        'status',
        'platform',
        'page_id',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'json',
        ];
    }

    protected static function newFactory(): \Database\Factories\CampaignFactory
    {
        return \Database\Factories\CampaignFactory::new();
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }
}
