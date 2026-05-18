<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_verifies_webhook_with_correct_token(): void
    {
        config()->set('services.meta.webhook_verify_token', 'test123');

        $response = $this->get('/webhook/meta?' . http_build_query([
            'hub_mode' => 'subscribe',
            'hub_verify_token' => 'test123',
            'hub_challenge' => 'challenge123',
        ]));

        $response->assertStatus(200);
        $response->assertSee('challenge123');
    }

    public function test_rejects_webhook_with_wrong_token(): void
    {
        config()->set('services.meta.webhook_verify_token', 'correct_token');

        $response = $this->get('/webhook/meta?' . http_build_query([
            'hub_mode' => 'subscribe',
            'hub_verify_token' => 'wrong_token',
            'hub_challenge' => 'test',
        ]));

        $response->assertStatus(403);
    }

    public function test_rejects_webhook_without_challenge(): void
    {
        config()->set('services.meta.webhook_verify_token', 'test123');

        $response = $this->get('/webhook/meta?' . http_build_query([
            'hub_mode' => 'subscribe',
            'hub_verify_token' => 'test123',
        ]));

        $response->assertStatus(403);
    }

    public function test_handles_comment_webhook(): void
    {
        $payload = [
            'entry' => [[
                'id' => 'page_1',
                'changes' => [[
                    'field' => 'feed',
                    'value' => [
                        'comment_id' => 'comment_1',
                        'post_id' => 'post_1',
                        'message' => 'كم السعر؟',
                        'from' => ['id' => 'user_1', 'name' => 'أحمد'],
                    ],
                ]],
            ]],
        ];

        $response = $this->postJson('/webhook/meta', $payload);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'ok']);
    }

    public function test_handles_messenger_message_webhook(): void
    {
        $payload = [
            'entry' => [[
                'id' => 'page_1',
                'messaging' => [[
                    'sender' => ['id' => 'user_1'],
                    'recipient' => ['id' => 'page_1'],
                    'timestamp' => 1700000000000,
                    'message' => [
                        'mid' => 'msg_1',
                        'text' => 'Hello, I have a question',
                    ],
                ]],
            ]],
        ];

        $response = $this->postJson('/webhook/meta', $payload);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'ok']);
    }

    public function test_handles_empty_webhook_payload(): void
    {
        $response = $this->postJson('/webhook/meta', []);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'ok']);
    }
}
