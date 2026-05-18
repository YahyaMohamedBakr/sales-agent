<?php

namespace App\Domains\Lead\Repositories;

use App\Domains\Lead\Models\Lead;
use App\Support\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

interface LeadRepositoryInterface extends BaseRepositoryInterface
{
    public function findByPsid(string $psid): ?Lead;

    public function findByPhone(string $phone): ?Lead;

    public function findByEmail(string $email): ?Lead;

    public function findQualified(int $minScore = 70): Collection;

    public function findByStatus(string $status): Collection;

    public function findByCampaign(string $campaignId): Collection;

    public function updateScore(string $id, int $score): Lead;

    public function markAs(string $id, string $status): Lead;

    public function addFieldValue(string $leadId, string $key, string $value): void;
}
