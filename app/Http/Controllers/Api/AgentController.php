<?php

namespace App\Http\Controllers\Api;

use App\Domains\Agent\Contracts\SmartRouterInterface;
use App\Domains\Agent\Enums\AIProvider;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentController extends Controller
{
    public function __construct(
        private SmartRouterInterface $router,
    ) {}

    public function health(): JsonResponse
    {
        return response()->json($this->router->availableProviders());
    }

    public function fullHealth(): JsonResponse
    {
        return response()->json([
            'available' => $this->router->availableProviders(),
            'report' => $this->router->healthReport(),
        ]);
    }

    public function chat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string',
            'provider' => 'nullable|string',
            'strategy' => 'nullable|string',
        ]);

        $provider = $validated['provider'] ?? null;
        $strategy = $validated['strategy'] ?? 'smart';

        try {
            $response = $this->router->chat(
                messages: [['role' => 'user', 'content' => $validated['message']]],
                preferred: $provider ? AIProvider::tryFrom($provider) : null,
            );

            return response()->json([
                'success' => true,
                'response' => $response->content,
                'model' => $response->model,
                'provider' => $response->provider,
                'tokens' => $response->totalTokens(),
                'time_ms' => $response->processingTimeMs,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 503);
        }
    }

    public function analyze(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'text' => 'required|string',
            'provider' => 'nullable|string',
        ]);

        $provider = $validated['provider'] ?? null;

        try {
            $analysis = $this->router->analyze(
                text: $validated['text'],
                preferred: $provider ? AIProvider::tryFrom($provider) : null,
            );

            return response()->json([
                'success' => true,
                'analysis' => $analysis,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 503);
        }
    }
}
