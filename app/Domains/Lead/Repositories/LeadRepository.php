<?php

namespace App\Domains\Lead\Repositories;

use App\Domains\Lead\Models\Lead;
use App\Domains\Lead\Models\LeadFieldValue;
use App\Support\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;

class LeadRepository extends BaseRepository implements LeadRepositoryInterface
{
    public function __construct(Lead $model)
    {
        parent::__construct($model);
    }

    public function findByPsid(string $psid): ?Lead
    {
        return $this->query()->where('psid', $psid)->first();
    }

    public function findByPhone(string $phone): ?Lead
    {
        return $this->query()->where('phone', $phone)->first();
    }

    public function findByEmail(string $email): ?Lead
    {
        return $this->query()->where('email', $email)->first();
    }

    public function findQualified(int $minScore = 70): Collection
    {
        return $this->query()
            ->where('score', '>=', $minScore)
            ->whereIn('status', ['new', 'contacted', 'qualifying', 'qualified'])
            ->orderBy('score', 'desc')
            ->get();
    }

    public function findByStatus(string $status): Collection
    {
        return $this->query()
            ->where('status', $status)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function findByCampaign(string $campaignId): Collection
    {
        return $this->query()
            ->where('campaign_id', $campaignId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function updateScore(string $id, int $score): Lead
    {
        $lead = $this->findById($id);
        $lead->update(['score' => $score]);

        if ($score >= 70 && $lead->status === 'qualifying') {
            $lead->update(['status' => 'qualified']);
        }

        return $lead->fresh();
    }

    public function markAs(string $id, string $status): Lead
    {
        $lead = $this->findById($id);
        $lead->update(['status' => $status]);

        return $lead->fresh();
    }

    public function addFieldValue(string $leadId, string $key, string $value): void
    {
        LeadFieldValue::updateOrCreate(
            ['lead_id' => $leadId, 'field_key' => $key],
            ['field_value' => $value],
        );
    }
}
