<?php

namespace App\Domains\Integration\Platforms;

use App\Domains\Integration\Contracts\SocialPlatformInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppPlatform implements SocialPlatformInterface
{
    private string $phoneNumberId;
    private ?string $accessToken;
    private string $baseUrl;

    public function __construct()
    {
        $this->phoneNumberId = config('services.whatsapp.phone_number_id', '');
        $this->accessToken = config('services.whatsapp.access_token');
        $this->baseUrl = 'https://graph.facebook.com/' . config('services.whatsapp.api_version', 'v21.0');
    }

    public function platformName(): string
    {
        return 'whatsapp';
    }

    public function setAccessToken(string $token): void
    {
        $this->accessToken = $token;
    }

    public function setPageId(string $pageId): void {}

    public function getPageInfo(): array
    {
        return [];
    }

    public function getComments(string $postId, array $params = []): Collection
    {
        return collect();
    }

    public function replyToComment(string $commentId, string $message): array
    {
        return [];
    }

    public function sendMessage(string $recipientId, string $message, array $options = []): array
    {
        $cleanNumber = preg_replace('/[^0-9]/', '', $recipientId);
        $cleanNumber = str_starts_with($cleanNumber, 'wa:') ? substr($cleanNumber, 3) : $cleanNumber;

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $cleanNumber,
            'type' => 'text',
            'text' => ['body' => $message],
        ];

        $response = Http::withToken($this->accessToken)
            ->timeout(15)
            ->post("{$this->baseUrl}/{$this->phoneNumberId}/messages", $payload);

        if (!$response->successful()) {
            $error = $response->json();
            Log::error('WhatsApp API error', [
                'status' => $response->status(),
                'error' => $error['error']['message'] ?? $response->body(),
            ]);
            throw new \RuntimeException('WhatsApp API: ' . ($error['error']['message'] ?? 'Unknown error'));
        }

        $data = $response->json();

        Log::info('WhatsApp: message sent', [
            'to' => $cleanNumber,
            'message_id' => $data['messages'][0]['id'] ?? null,
        ]);

        return $data;
    }

    public function getConversation(string $conversationId): array
    {
        return [];
    }

    public function markAsRead(string $recipientId): bool
    {
        $cleanNumber = preg_replace('/[^0-9]/', '', $recipientId);

        $response = Http::withToken($this->accessToken)
            ->post("{$this->baseUrl}/{$this->phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'status' => 'read',
                'message_id' => $recipientId,
            ]);

        return $response->successful();
    }

    public static function parseWebhookPayload(array $payload): array
    {
        $events = [];

        foreach ($payload['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                if (($change['field'] ?? '') !== 'messages') continue;

                $value = $change['value'] ?? [];
                $metadata = $value['metadata'] ?? [];
                $phoneNumberId = $metadata['phone_number_id'] ?? '';

                foreach ($value['messages'] ?? [] as $msg) {
                    $from = $msg['from'] ?? '';
                    $text = '';

                    if (($msg['type'] ?? '') === 'text') {
                        $text = $msg['text']['body'] ?? '';
                    } elseif (($msg['type'] ?? '') === 'interactive') {
                        $text = $msg['interactive']['button_reply']['title']
                            ?? $msg['interactive']['list_reply']['title']
                            ?? '';
                    }

                    $events[] = [
                        'type' => 'message',
                        'channel' => 'whatsapp',
                        'sender_id' => "wa:{$from}",
                        'from_name' => $value['contacts'][0]['profile']['name'] ?? '',
                        'message' => $text,
                        'message_id' => $msg['id'] ?? '',
                        'timestamp' => $msg['timestamp'] ?? null,
                        'phone_number_id' => $phoneNumberId,
                    ];
                }

                foreach ($value['statuses'] ?? [] as $status) {
                    $events[] = [
                        'type' => 'status',
                        'channel' => 'whatsapp',
                        'message_id' => $status['id'] ?? '',
                        'status' => $status['status'] ?? '',
                        'timestamp' => $status['timestamp'] ?? null,
                    ];
                }
            }
        }

        return $events;
    }
}
