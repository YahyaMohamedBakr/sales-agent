<?php

namespace App\Domains\Integration\Platforms;

use App\Domains\Integration\Contracts\SocialPlatformInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaPlatform implements SocialPlatformInterface
{
    private const GRAPH_API_URL = 'https://graph.facebook.com/v21.0';

    private ?string $accessToken;
    private ?string $pageId;
    private ?string $appSecret;

    public function __construct()
    {
        $this->accessToken = config('services.meta.page_access_token');
        $this->pageId = config('services.meta.page_id');
        $this->appSecret = config('services.meta.app_secret');
    }

    public function platformName(): string
    {
        return 'meta';
    }

    public function setAccessToken(string $token): void
    {
        $this->accessToken = $token;
    }

    public function setPageId(string $pageId): void
    {
        $this->pageId = $pageId;
    }

    public function getPageInfo(): array
    {
        $response = Http::get($this->url("/{$this->pageId}"), [
            'fields' => 'id,name,username,link,about,fan_count',
            'access_token' => $this->accessToken,
        ]);

        $this->checkError($response, 'getPageInfo');

        return $response->json();
    }

    public function getComments(string $postId, array $params = []): Collection
    {
        $response = Http::get($this->url("/{$postId}/comments"), [
            'fields' => 'id,message,from,created_time,parent,attachment',
            'order' => 'chronological',
            'filter' => 'stream',
            'access_token' => $this->accessToken,
            ...$params,
        ]);

        $this->checkError($response, 'getComments');

        return collect($response->json()['data'] ?? []);
    }

    public function replyToComment(string $commentId, string $message): array
    {
        $response = Http::post($this->url("/{$commentId}/replies"), [
            'message' => $message,
            'access_token' => $this->accessToken,
        ]);

        $this->checkError($response, 'replyToComment');

        Log::info('Meta: replied to comment', [
            'comment_id' => $commentId,
            'message' => $message,
        ]);

        return $response->json();
    }

    public function sendMessage(string $recipientId, string $message, array $options = []): array
    {
        $payload = [
            'recipient' => ['id' => $recipientId],
            'message' => ['text' => $message],
            'messaging_type' => $options['messaging_type'] ?? 'RESPONSE',
            'access_token' => $this->accessToken,
        ];

        $response = Http::post($this->url("/me/messages"), $payload);

        $this->checkError($response, 'sendMessage');

        Log::info('Meta: sent message', [
            'recipient_id' => $recipientId,
            'type' => $payload['messaging_type'],
        ]);

        return $response->json();
    }

    public function getConversation(string $conversationId): array
    {
        $response = Http::get($this->url("/{$conversationId}"), [
            'fields' => 'id,link,messages{sender,message,created_time},participants',
            'access_token' => $this->accessToken,
        ]);

        $this->checkError($response, 'getConversation');

        return $response->json();
    }

    public function markAsRead(string $recipientId): bool
    {
        $response = Http::post($this->url("/me/messages"), [
            'recipient' => ['id' => $recipientId],
            'sender_action' => 'mark_seen',
            'access_token' => $this->accessToken,
        ]);

        return $response->successful();
    }

    /**
     * Parse incoming webhook payload from Meta
     */
    public static function parseWebhookPayload(array $payload): array
    {
        $events = [];

        foreach ($payload['entry'] ?? [] as $entry) {
            $pageId = $entry['id'] ?? '';
            $time = $entry['time'] ?? null;

            // Comment events
            foreach ($entry['changes'] ?? [] as $change) {
                if (($change['field'] ?? '') === 'feed') {
                    $value = $change['value'] ?? [];

                    if (isset($value['comment_id'])) {
                        $events[] = [
                            'type' => 'comment',
                            'comment_id' => $value['comment_id'],
                            'post_id' => $value['post_id'] ?? null,
                            'message' => $value['message'] ?? '',
                            'from_id' => $value['from']['id'] ?? '',
                            'from_name' => $value['from']['name'] ?? '',
                            'parent_id' => $value['parent_id'] ?? null,
                            'is_reply' => isset($value['parent_id']),
                            'created_time' => $value['created_time'] ?? $time,
                            'page_id' => $pageId,
                        ];
                    }
                }
            }

            // Messenger events
            foreach ($entry['messaging'] ?? [] as $messaging) {
                $senderId = $messaging['sender']['id'] ?? '';

                // Message received
                if (isset($messaging['message'])) {
                    $events[] = [
                        'type' => 'message',
                        'sender_id' => $senderId,
                        'recipient_id' => $messaging['recipient']['id'] ?? '',
                        'message_id' => $messaging['message']['mid'] ?? '',
                        'message' => $messaging['message']['text'] ?? '',
                        'attachments' => $messaging['message']['attachments'] ?? [],
                        'is_echo' => $messaging['message']['is_echo'] ?? false,
                        'timestamp' => $messaging['timestamp'] ?? $time,
                        'page_id' => $pageId,
                    ];
                }

                // Postback (button clicks)
                if (isset($messaging['postback'])) {
                    $events[] = [
                        'type' => 'postback',
                        'sender_id' => $senderId,
                        'payload' => $messaging['postback']['payload'] ?? '',
                        'title' => $messaging['postback']['title'] ?? '',
                        'timestamp' => $messaging['timestamp'] ?? $time,
                        'page_id' => $pageId,
                    ];
                }

                // Message delivered / read
                if (isset($messaging['delivery'])) {
                    $events[] = [
                        'type' => 'delivery',
                        'sender_id' => $senderId,
                        'watermark' => $messaging['delivery']['watermark'] ?? 0,
                        'page_id' => $pageId,
                    ];
                }
            }
        }

        return $events;
    }

    private function url(string $path): string
    {
        return self::GRAPH_API_URL . $path;
    }

    private function checkError(\Illuminate\Http\Client\Response $response, string $context): void
    {
        if ($response->failed()) {
            $error = $response->json();

            Log::error("Meta API error [{$context}]", [
                'status' => $response->status(),
                'error' => $error['error']['message'] ?? $response->body(),
            ]);

            $msg = $error['error']['message'] ?? 'Unknown Meta API error';
            $code = $error['error']['code'] ?? 0;

            if ($code === 190) {
                throw new \RuntimeException('Meta access token expired. Please reconnect.');
            }

            if ($code === 368) {
                throw new \RuntimeException('Meta rate limit hit. Try again later.');
            }

            throw new \RuntimeException("Meta API: {$msg}");
        }
    }
}
