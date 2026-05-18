<?php

namespace App\Http\Controllers;

use App\Domains\Agent\Services\Orchestrator;
use App\Domains\Integration\Platforms\MetaPlatform;
use App\Domains\Integration\Platforms\WhatsAppPlatform;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(
        private Orchestrator $orchestrator,
    ) {}

    public function verify(Request $request)
    {
        $mode = $request->input('hub_mode');
        $token = $request->input('hub_verify_token');
        $challenge = $request->input('hub_challenge');

        $expectedToken = config('services.meta.webhook_verify_token');

        if ($mode === 'subscribe' && $token === $expectedToken && $challenge) {
            Log::info('Meta webhook verified');
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        Log::warning('Meta webhook verification failed', [
            'expected' => $expectedToken,
            'received' => $token,
        ]);

        return response('Forbidden', 403);
    }

    public function handle(Request $request)
    {
        $payload = $request->all();

        Log::info('Meta webhook received', [
            'object' => $payload['object'] ?? 'unknown',
        ]);

        $events = MetaPlatform::parseWebhookPayload($payload);

        foreach ($events as $event) {
            try {
                match ($event['type']) {
                    'comment' => $this->handleComment($event),
                    'message' => $this->handleMessage($event),
                    'postback' => $this->handlePostback($event),
                    default => Log::debug('Unknown event type', $event),
                };
            } catch (\Throwable $e) {
                Log::error('Error processing webhook event', [
                    'type' => $event['type'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return response()->json(['status' => 'ok']);
    }

    public function verifyWhatsApp(Request $request)
    {
        $mode = $request->input('hub_mode');
        $token = $request->input('hub_verify_token');
        $challenge = $request->input('hub_challenge');

        $expectedToken = config('services.meta.webhook_verify_token');

        if ($mode === 'subscribe' && $token === $expectedToken) {
            Log::info('WhatsApp webhook verified');
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        return response('Forbidden', 403);
    }

    public function handleWhatsApp(Request $request)
    {
        $payload = $request->all();

        Log::info('WhatsApp webhook received');

        $events = WhatsAppPlatform::parseWebhookPayload($payload);

        foreach ($events as $event) {
            try {
                match ($event['type']) {
                    'message' => $this->handleMessage($event),
                    'status' => Log::debug('WhatsApp status update', $event),
                    default => Log::debug('Unknown WhatsApp event', $event),
                };
            } catch (\Throwable $e) {
                Log::error('Error processing WhatsApp event', [
                    'type' => $event['type'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return response()->json(['status' => 'ok']);
    }

    private function handleComment(array $event): void
    {
        Log::info('Processing comment', [
            'from' => $event['from_name'] ?? '',
            'message' => mb_substr($event['message'] ?? '', 0, 100),
        ]);

        $this->orchestrator->handleComment($event);
    }

    private function handleMessage(array $event): void
    {
        if (($event['is_echo'] ?? false) || ($event['status'] ?? '')) {
            return;
        }

        Log::info('Processing message', [
            'channel' => $event['channel'] ?? 'messenger',
            'from' => $event['sender_id'] ?? '',
            'message' => mb_substr($event['message'] ?? '', 0, 100),
        ]);

        $this->orchestrator->handleMessage($event);
    }

    private function handlePostback(array $event): void
    {
        Log::info('Processing postback', [
            'payload' => $event['payload'] ?? '',
        ]);

        $this->orchestrator->handlePostback($event);
    }
}
