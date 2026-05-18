<?php

namespace App\Console\Commands;

use App\Domains\Integration\Contracts\SocialPlatformInterface;
use Illuminate\Console\Command;

class TestMetaConnection extends Command
{
    protected $signature = 'meta:test
        {--action=info : Action to test (info, comments, send-message)}
        {--post-id= : Post ID for comments action}
        {--recipient= : Recipient PSID for send-message action}
        {--message= : Message to send}
        {--comment-id= : Comment ID for reply action}';

    protected $description = 'Test Meta (Facebook) connection and API calls';

    public function handle(SocialPlatformInterface $platform): int
    {
        $action = $this->option('action');

        $this->newLine();
        $this->info('🔵 Meta Connection Test');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━');

        return match ($action) {
            'info' => $this->testPageInfo($platform),
            'comments' => $this->testComments($platform),
            'send-message' => $this->testSendMessage($platform),
            default => $this->showHelp(),
        };
    }

    private function testPageInfo(SocialPlatformInterface $platform): int
    {
        $this->line('Fetching page info...');

        try {
            $info = $platform->getPageInfo();

            $username = $info['username'] ?? '-';
            $followers = $info['fan_count'] ?? '-';

            $this->line("  Page ID:     <fg=green>{$info['id']}</>");
            $this->line("  Name:        <fg=yellow>{$info['name']}</>");
            $this->line("  Username:    {$username}");
            $this->line("  Followers:   {$followers}");
            $this->line("  Status:      <fg=green>✅ Connected</>");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("  Failed: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    private function testComments(SocialPlatformInterface $platform): int
    {
        $postId = $this->option('post-id');
        if (!$postId) {
            $this->error('--post-id is required');
            return self::FAILURE;
        }

        $this->line("Fetching comments for post {$postId}...");

        try {
            $comments = $platform->getComments($postId);
            $count = $comments->count();

            $this->line("  Comments:    <fg=green>{$count}</>");

            foreach ($comments->take(5) as $comment) {
                $from = $comment['from']['name'] ?? 'Unknown';
                $msg = mb_substr($comment['message'] ?? '', 0, 80);
                $this->line("  - <fg=cyan>{$from}:</> {$msg}");
            }

            if ($count > 5) {
                $remaining = $count - 5;
                $this->line("  ... and {$remaining} more");
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("  Failed: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    private function testSendMessage(SocialPlatformInterface $platform): int
    {
        $recipient = $this->option('recipient') ?? $platform->platformName();
        $message = $this->option('message') ?? 'مرحباً! هذه رسالة اختبار من AI Sales Agent.';

        $this->line("Sending message to {$recipient}...");

        try {
            $result = $platform->sendMessage($recipient, $message);

            $this->line("  Recipient:   <fg=green>{$recipient}</>");
            $this->line("  Message:     {$message}");
            $msgId = $result['message_id'] ?? 'N/A';
            $this->line("  Message ID:  <fg=yellow>{$msgId}</>");
            $this->line("  Status:      <fg=green>✅ Sent</>");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("  Failed: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    private function showHelp(): int
    {
        $this->line('Usage:');
        $this->line('  php artisan meta:test --action=info');
        $this->line('  php artisan meta:test --action=comments --post-id={post_id}');
        $this->line('  php artisan meta:test --action=send-message --recipient={psid} --message="Hello"');

        return self::SUCCESS;
    }
}
