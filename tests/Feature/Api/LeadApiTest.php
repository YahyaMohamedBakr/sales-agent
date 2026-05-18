<?php

namespace Tests\Feature\Api;

use App\Domains\Campaign\Models\Campaign;
use App\Domains\Lead\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authenticate();
    }

    public function test_lists_leads(): void
    {
        Lead::factory()->count(3)->create();

        $response = $this->getJson('/api/leads');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data',
            'current_page',
            'last_page',
            'total',
        ]);
        $this->assertCount(3, $response->json('data'));
    }

    public function test_filters_leads_by_status(): void
    {
        Lead::factory()->create(['status' => 'new']);
        Lead::factory()->create(['status' => 'qualified']);
        Lead::factory()->create(['status' => 'qualified']);

        $response = $this->getJson('/api/leads?status=qualified');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_filters_leads_by_source(): void
    {
        Lead::factory()->create(['source' => 'comment']);
        Lead::factory()->create(['source' => 'messenger']);
        Lead::factory()->create(['source' => 'messenger']);

        $response = $this->getJson('/api/leads?source=messenger');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_searches_leads_by_name(): void
    {
        Lead::factory()->create(['name' => 'أحمد محمد']);
        Lead::factory()->create(['name' => 'محمد علي']);
        Lead::factory()->create(['name' => 'خالد سعد']);

        $response = $this->getJson('/api/leads?search=محمد');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_filters_leads_by_score_range(): void
    {
        Lead::factory()->create(['score' => 30]);
        Lead::factory()->create(['score' => 50]);
        Lead::factory()->create(['score' => 80]);

        $response = $this->getJson('/api/leads?score_min=70');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_creates_lead(): void
    {
        $campaign = Campaign::factory()->create();

        $response = $this->postJson('/api/leads', [
            'name' => 'أحمد',
            'phone' => '0512345678',
            'email' => 'ahmed@example.com',
            'source' => 'comment',
            'campaign_id' => $campaign->id,
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'name' => 'أحمد',
            'source' => 'comment',
        ]);
        $this->assertDatabaseHas('leads', [
            'name' => 'أحمد',
            'source' => 'comment',
        ]);
    }

    public function test_requires_source_to_create_lead(): void
    {
        $response = $this->postJson('/api/leads', [
            'name' => 'أحمد',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['source']);
    }

    public function test_shows_lead(): void
    {
        $lead = Lead::factory()->create();

        $response = $this->getJson("/api/leads/{$lead->id}");

        $response->assertStatus(200);
        $response->assertJson(['id' => $lead->id]);
    }

    public function test_returns_404_for_nonexistent_lead(): void
    {
        $response = $this->getJson('/api/leads/nonexistent-uuid');

        $response->assertStatus(404);
    }

    public function test_updates_lead(): void
    {
        $lead = Lead::factory()->create(['name' => 'قديم']);

        $response = $this->putJson("/api/leads/{$lead->id}", [
            'name' => 'جديد',
            'score' => 85,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'name' => 'جديد',
            'score' => 85,
        ]);
    }

    public function test_deletes_lead(): void
    {
        $lead = Lead::factory()->create();

        $response = $this->deleteJson("/api/leads/{$lead->id}");

        $response->assertStatus(200);
        $response->assertJson(['status' => 'deleted']);
        $this->assertSoftDeleted($lead);
    }

    public function test_adds_field_value_to_lead(): void
    {
        $lead = Lead::factory()->create();

        $response = $this->postJson("/api/leads/{$lead->id}/fields", [
            'field_key' => 'city',
            'field_value' => 'الرياض',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('lead_field_values', [
            'lead_id' => $lead->id,
            'field_key' => 'city',
            'field_value' => 'الرياض',
        ]);
    }

    public function test_requires_field_key_for_add_field(): void
    {
        $lead = Lead::factory()->create();

        $response = $this->postJson("/api/leads/{$lead->id}/fields", [
            'field_value' => 'test',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['field_key']);
    }
}
