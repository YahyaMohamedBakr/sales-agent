<?php

namespace App\Domains\Campaign\Repositories;

use App\Domains\Campaign\Models\Campaign;
use App\Support\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

interface CampaignRepositoryInterface extends BaseRepositoryInterface
{
    public function findByMetaAdId(string $metaAdId): ?Campaign;

    public function findByStatus(string $status): Collection;

    public function findByPlatform(string $platform): Collection;

    public function findActive(): Collection;
}
