<?php

namespace App\Domains\Campaign\Repositories;

use App\Domains\Campaign\Models\Campaign;
use App\Support\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;

class CampaignRepository extends BaseRepository implements CampaignRepositoryInterface
{
    public function __construct(Campaign $model)
    {
        parent::__construct($model);
    }

    public function findByMetaAdId(string $metaAdId): ?Campaign
    {
        return $this->query()->where('meta_ad_id', $metaAdId)->first();
    }

    public function findByStatus(string $status): Collection
    {
        return $this->query()->where('status', $status)->get();
    }

    public function findByPlatform(string $platform): Collection
    {
        return $this->query()->where('platform', $platform)->get();
    }

    public function findActive(): Collection
    {
        return $this->query()->where('status', 'active')->get();
    }
}
