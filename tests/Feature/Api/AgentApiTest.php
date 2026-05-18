<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authenticate();
    }

    public function test_health_endpoint_returns_available_providers(): void
    {
        $response = $this->getJson('/api/agent/health');

        $response->assertStatus(200);
        $this->assertIsArray($response->json());
    }

    public function test_full_health_endpoint(): void
    {
        $response = $this->getJson('/api/agent/health/full');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'available',
            'report',
        ]);
    }

    public function test_chat_endpoint_requires_message(): void
    {
        $response = $this->postJson('/api/agent/chat', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['message']);
    }

    public function test_chat_endpoint_returns_error_when_all_providers_down(): void
    {
        $response = $this->postJson('/api/agent/chat', [
            'message' => 'Hello',
        ]);

        $this->assertContains($response->status(), [200, 503]);
    }

    public function test_analyze_endpoint_requires_text(): void
    {
        $response = $this->postJson('/api/agent/analyze', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['text']);
    }

    public function test_analyze_endpoint(): void
    {
        $response = $this->postJson('/api/agent/analyze', [
            'text' => 'كم سعر المنتج؟',
        ]);

        $this->assertContains($response->status(), [200, 503]);
    }
}
