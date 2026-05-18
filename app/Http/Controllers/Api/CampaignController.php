<?php

namespace App\Http\Controllers\Api;

use App\Domains\Campaign\Models\Campaign;
use App\Domains\Campaign\Repositories\CampaignRepositoryInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    public function __construct(
        private CampaignRepositoryInterface $campaigns,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Campaign::withCount('leads');

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        return response()->json($query->orderBy('created_at', 'desc')->paginate(20));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'meta_ad_id' => 'nullable|string|unique:campaigns',
            'status' => 'nullable|string',
            'platform' => 'nullable|string',
            'page_id' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        $campaign = $this->campaigns->create($validated);

        return response()->json($campaign, 201);
    }

    public function show(string $id): JsonResponse
    {
        $campaign = Campaign::withCount('leads')->with('leads')->findOrFail($id);

        return response()->json($campaign);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'status' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        $campaign = $this->campaigns->update($id, $validated);

        return response()->json($campaign);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->campaigns->delete($id);

        return response()->json(['status' => 'deleted']);
    }

    public function stats(string $id): JsonResponse
    {
        $campaign = Campaign::withCount([
            'leads',
            'leads as qualified_count' => fn ($q) => $q->where('status', 'qualified'),
            'leads as converted_count' => fn ($q) => $q->where('status', 'converted'),
        ])->findOrFail($id);

        $total = $campaign->leads_count;
        $qualified = $campaign->qualified_count;
        $converted = $campaign->converted_count;

        return response()->json([
            'total_leads' => $total,
            'qualified' => $qualified,
            'converted' => $converted,
            'qualification_rate' => $total > 0 ? round(($qualified / $total) * 100, 1) : 0,
            'conversion_rate' => $total > 0 ? round(($converted / $total) * 100, 1) : 0,
        ]);
    }
}
