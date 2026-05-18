<?php

namespace Tests\Unit\Domains\Lead;

use App\Domains\Lead\Models\Lead;
use App\Domains\Lead\Models\LeadFieldValue;
use App\Domains\Lead\Repositories\LeadRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private LeadRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new LeadRepository(new Lead);
    }

    public function test_creates_lead_via_repository(): void
    {
        $lead = $this->repository->create([
            'name' => 'أحمد',
            'source' => 'comment',
            'status' => 'new',
        ]);

        $this->assertNotNull($lead->id);
        $this->assertEquals('أحمد', $lead->name);
        $this->assertEquals('comment', $lead->source);
        $this->assertEquals('new', $lead->status);
    }

    public function test_finds_lead_by_psid(): void
    {
        Lead::factory()->create(['psid' => 'test_psid_001']);

        $lead = $this->repository->findByPsid('test_psid_001');

        $this->assertNotNull($lead);
        $this->assertEquals('test_psid_001', $lead->psid);
    }

    public function test_returns_null_for_nonexistent_psid(): void
    {
        $this->assertNull($this->repository->findByPsid('nonexistent'));
    }

    public function test_finds_lead_by_phone(): void
    {
        Lead::factory()->create(['phone' => '0512345678']);

        $lead = $this->repository->findByPhone('0512345678');

        $this->assertNotNull($lead);
        $this->assertEquals('0512345678', $lead->phone);
    }

    public function test_finds_lead_by_email(): void
    {
        Lead::factory()->create(['email' => 'test@example.com']);

        $lead = $this->repository->findByEmail('test@example.com');

        $this->assertNotNull($lead);
        $this->assertEquals('test@example.com', $lead->email);
    }

    public function test_finds_qualified_leads(): void
    {
        Lead::factory()->count(3)->qualified()->create();
        Lead::factory()->create(['score' => 30, 'status' => 'new']);

        $qualified = $this->repository->findQualified(70);

        $this->assertCount(3, $qualified);
        foreach ($qualified as $lead) {
            $this->assertGreaterThanOrEqual(70, $lead->score);
        }
    }

    public function test_finds_leads_by_status(): void
    {
        Lead::factory()->count(2)->create(['status' => 'new']);
        Lead::factory()->create(['status' => 'qualified']);

        $newLeads = $this->repository->findByStatus('new');

        $this->assertCount(2, $newLeads);
    }

    public function test_finds_leads_by_campaign(): void
    {
        $campaign = \App\Domains\Campaign\Models\Campaign::factory()->create();
        Lead::factory()->count(2)->create(['campaign_id' => $campaign->id]);
        Lead::factory()->create();

        $campaignLeads = $this->repository->findByCampaign($campaign->id);

        $this->assertCount(2, $campaignLeads);
    }

    public function test_updates_lead_score(): void
    {
        $lead = Lead::factory()->create(['score' => 30, 'status' => 'qualifying']);

        $this->repository->updateScore($lead->id, 80);

        $lead->refresh();
        $this->assertEquals(80, $lead->score);
    }

    public function test_updating_score_to_70_qualifies_lead(): void
    {
        $lead = Lead::factory()->create(['score' => 50, 'status' => 'qualifying']);

        $this->repository->updateScore($lead->id, 75);

        $lead->refresh();
        $this->assertEquals('qualified', $lead->status);
    }

    public function test_marks_lead_as(): void
    {
        $lead = Lead::factory()->create(['status' => 'new']);

        $this->repository->markAs($lead->id, 'qualified');

        $lead->refresh();
        $this->assertEquals('qualified', $lead->status);
    }

    public function test_adds_field_value(): void
    {
        $lead = Lead::factory()->create();

        $this->repository->addFieldValue($lead->id, 'city', 'الرياض');

        $this->assertDatabaseHas('lead_field_values', [
            'lead_id' => $lead->id,
            'field_key' => 'city',
            'field_value' => 'الرياض',
        ]);
    }

    public function test_updates_existing_field_value(): void
    {
        $lead = Lead::factory()->create();
        LeadFieldValue::create([
            'lead_id' => $lead->id,
            'field_key' => 'city',
            'field_value' => 'جدة',
        ]);

        $this->repository->addFieldValue($lead->id, 'city', 'الرياض');

        $this->assertDatabaseCount('lead_field_values', 1);
        $this->assertDatabaseHas('lead_field_values', [
            'lead_id' => $lead->id,
            'field_key' => 'city',
            'field_value' => 'الرياض',
        ]);
    }

    public function test_deletes_lead(): void
    {
        $lead = Lead::factory()->create();

        $result = $this->repository->delete($lead->id);

        $this->assertTrue($result);
        $this->assertSoftDeleted($lead);
    }
}
