<?php

namespace App\Domains\Integration\Contracts;

use App\Domains\Lead\Models\Lead;
use Illuminate\Support\Collection;

interface SocialPlatformInterface
{
    public function platformName(): string;

    public function getPageInfo(): array;

    public function getComments(string $postId, array $params = []): Collection;

    public function replyToComment(string $commentId, string $message): array;

    public function sendMessage(string $recipientId, string $message, array $options = []): array;

    public function getConversation(string $conversationId): array;

    public function markAsRead(string $recipientId): bool;

    public function setAccessToken(string $token): void;

    public function setPageId(string $pageId): void;
}
