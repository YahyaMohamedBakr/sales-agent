<?php

namespace App\Http\Controllers\Api;

use App\Domains\Conversation\Models\Conversation;
use App\Domains\Conversation\Repositories\ConversationRepositoryInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    public function __construct(
        private ConversationRepositoryInterface $conversations,
    ) {}

    public function index(string $leadId): JsonResponse
    {
        $messages = $this->conversations->findByLead($leadId);

        return response()->json($messages);
    }

    public function store(Request $request, string $leadId): JsonResponse
    {
        $validated = $request->validate([
            'channel' => 'required|string',
            'message' => 'required|string',
            'direction' => 'required|string|in:inbound,outbound',
            'metadata' => 'nullable|array',
        ]);

        $conversation = $this->conversations->logMessage(
            $leadId,
            $validated['channel'],
            $validated['message'],
            $validated['direction'],
            $validated['metadata'] ?? [],
        );

        return response()->json($conversation, 201);
    }
}
