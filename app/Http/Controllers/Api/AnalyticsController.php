<?php

namespace App\Http\Controllers\Api;

use App\Domains\Lead\Models\Lead;
use App\Domains\Campaign\Models\Campaign;
use App\Domains\Conversation\Models\Conversation;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function overview(): JsonResponse
    {
        $totalLeads = Lead::count();
        $qualified = Lead::where('score', '>=', 70)->count();
        $converted = Lead::where('status', 'converted')->count();
        $active = Lead::whereIn('status', ['new', 'contacted', 'qualifying'])->count();
        $totalConversations = Conversation::count();
        $totalCampaigns = Campaign::count();

        return response()->json([
            'total_leads' => $totalLeads,
            'qualified' => $qualified,
            'converted' => $converted,
            'active' => $active,
            'total_conversations' => $totalConversations,
            'total_campaigns' => $totalCampaigns,
            'qualification_rate' => $totalLeads > 0 ? round(($qualified / $totalLeads) * 100, 1) : 0,
            'conversion_rate' => $totalLeads > 0 ? round(($converted / $totalLeads) * 100, 1) : 0,
        ]);
    }

    public function leadsBySource(): JsonResponse
    {
        $data = Lead::selectRaw('source, count(*) as count')
            ->groupBy('source')
            ->orderByDesc('count')
            ->get();

        return response()->json($data);
    }

    public function leadsByDay(Request $request): JsonResponse
    {
        $days = min((int) ($request->input('days', 30)), 90);

        $data = Lead::selectRaw("DATE(created_at) as date, count(*) as count")
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json($data);
    }

    public function leadsByStatus(): JsonResponse
    {
        $data = Lead::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->orderByDesc('count')
            ->get();

        return response()->json($data);
    }

    public function topCampaigns(): JsonResponse
    {
        $data = Campaign::withCount([
            'leads',
            'leads as qualified' => fn ($q) => $q->where('score', '>=', 70),
        ])
            ->orderByDesc('leads_count')
            ->limit(10)
            ->get();

        return response()->json($data);
    }

    public function agentPerformance(Request $request): JsonResponse
    {
        $days = min((int) ($request->input('days', 7)), 90);

        $data = \App\Domains\Agent\Models\AgentAction::selectRaw(
            "agent_type, action_type, model_used, count(*) as count, avg(tokens_used) as avg_tokens, avg(processing_time_ms) as avg_time_ms",
        )
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('agent_type', 'action_type', 'model_used')
            ->orderByDesc('count')
            ->get();

        return response()->json($data);
    }
}
