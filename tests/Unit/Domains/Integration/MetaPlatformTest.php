<?php

namespace Tests\Unit\Domains\Integration;

use App\Domains\Integration\Platforms\MetaPlatform;
use Tests\TestCase;

class MetaPlatformTest extends TestCase
{
    public function test_parses_comment_payload(): void
    {
        $payload = [
            'entry' => [[
                'id' => '123',
                'changes' => [[
                    'field' => 'feed',
                    'value' => [
                        'comment_id' => '456',
                        'post_id' => '789',
                        'message' => 'مرحبا',
                        'from' => ['id' => '111', 'name' => 'أحمد'],
                        'created_time' => '2025-01-01T00:00:00+0000',
                    ],
                ]],
            ]],
        ];

        $events = MetaPlatform::parseWebhookPayload($payload);

        $this->assertCount(1, $events);
        $this->assertEquals('comment', $events[0]['type']);
        $this->assertEquals('456', $events[0]['comment_id']);
        $this->assertEquals('مرحبا', $events[0]['message']);
        $this->assertEquals('111', $events[0]['from_id']);
        $this->assertEquals('أحمد', $events[0]['from_name']);
        $this->assertEquals('789', $events[0]['post_id']);
    }

    public function test_parses_comment_reply(): void
    {
        $payload = [
            'entry' => [[
                'id' => 'page_1',
                'changes' => [[
                    'field' => 'feed',
                    'value' => [
                        'comment_id' => 'reply_1',
                        'post_id' => 'post_1',
                        'message' => 'شكراً',
                        'from' => ['id' => 'user_1', 'name' => 'محمد'],
                        'parent_id' => 'parent_comment_1',
                        'created_time' => '2025-01-01T00:00:00+0000',
                    ],
                ]],
            ]],
        ];

        $events = MetaPlatform::parseWebhookPayload($payload);

        $this->assertCount(1, $events);
        $this->assertTrue($events[0]['is_reply']);
        $this->assertEquals('parent_comment_1', $events[0]['parent_id']);
    }

    public function test_parses_messenger_message(): void
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
                        'text' => 'Hello',
                    ],
                ]],
            ]],
        ];

        $events = MetaPlatform::parseWebhookPayload($payload);

        $this->assertCount(1, $events);
        $this->assertEquals('message', $events[0]['type']);
        $this->assertEquals('user_1', $events[0]['sender_id']);
        $this->assertEquals('Hello', $events[0]['message']);
        $this->assertFalse($events[0]['is_echo']);
    }

    public function test_parses_messenger_postback(): void
    {
        $payload = [
            'entry' => [[
                'id' => 'page_1',
                'messaging' => [[
                    'sender' => ['id' => 'user_1'],
                    'recipient' => ['id' => 'page_1'],
                    'timestamp' => 1700000000000,
                    'postback' => [
                        'payload' => 'GET_STARTED',
                        'title' => 'ابدأ',
                    ],
                ]],
            ]],
        ];

        $events = MetaPlatform::parseWebhookPayload($payload);

        $this->assertCount(1, $events);
        $this->assertEquals('postback', $events[0]['type']);
        $this->assertEquals('GET_STARTED', $events[0]['payload']);
        $this->assertEquals('ابدأ', $events[0]['title']);
    }

    public function test_parses_delivery_event(): void
    {
        $payload = [
            'entry' => [[
                'id' => 'page_1',
                'messaging' => [[
                    'sender' => ['id' => 'user_1'],
                    'delivery' => [
                        'watermark' => 1700000000000,
                    ],
                ]],
            ]],
        ];

        $events = MetaPlatform::parseWebhookPayload($payload);

        $this->assertCount(1, $events);
        $this->assertEquals('delivery', $events[0]['type']);
    }

    public function test_returns_empty_array_for_empty_payload(): void
    {
        $events = MetaPlatform::parseWebhookPayload([]);

        $this->assertEmpty($events);
    }

    public function test_returns_empty_array_for_payload_without_entry(): void
    {
        $events = MetaPlatform::parseWebhookPayload(['object' => 'page']);

        $this->assertEmpty($events);
    }

    public function test_handles_multiple_entries_and_events(): void
    {
        $payload = [
            'entry' => [[
                'id' => 'page_1',
                'changes' => [[
                    'field' => 'feed',
                    'value' => [
                        'comment_id' => 'c1',
                        'message' => 'first',
                        'from' => ['id' => 'u1', 'name' => 'User 1'],
                    ],
                ]],
                'messaging' => [[
                    'sender' => ['id' => 'u2'],
                    'recipient' => ['id' => 'page_1'],
                    'message' => ['mid' => 'm1', 'text' => 'second'],
                ]],
            ]],
        ];

        $events = MetaPlatform::parseWebhookPayload($payload);

        $this->assertCount(2, $events);
        $this->assertEquals('comment', $events[0]['type']);
        $this->assertEquals('message', $events[1]['type']);
    }

    public function test_ignores_unknown_change_field(): void
    {
        $payload = [
            'entry' => [[
                'id' => 'page_1',
                'changes' => [[
                    'field' => 'unknown_field',
                    'value' => ['some_data' => 'test'],
                ]],
            ]],
        ];

        $events = MetaPlatform::parseWebhookPayload($payload);

        $this->assertEmpty($events);
    }

    public function test_extracts_page_id_from_entry(): void
    {
        $payload = [
            'entry' => [[
                'id' => 'page_123',
                'changes' => [[
                    'field' => 'feed',
                    'value' => [
                        'comment_id' => 'c1',
                        'message' => 'test',
                        'from' => ['id' => 'u1', 'name' => 'Test'],
                    ],
                ]],
            ]],
        ];

        $events = MetaPlatform::parseWebhookPayload($payload);

        $this->assertEquals('page_123', $events[0]['page_id']);
    }
}
