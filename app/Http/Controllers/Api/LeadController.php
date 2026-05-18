<?php

namespace App\Http\Controllers\Api;

use App\Domains\Lead\Events\LeadCreated;
use App\Domains\Lead\Models\Lead;
use App\Domains\Lead\Repositories\LeadRepositoryInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    public function __construct(
        private LeadRepositoryInterface $leads,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Lead::query()->with('campaign');

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($source = $request->input('source')) {
            $query->where('source', $source);
        }

        if ($campaignId = $request->input('campaign_id')) {
            $query->where('campaign_id', $campaignId);
        }

        if ($min = $request->input('score_min')) {
            $query->where('score', '>=', (int) $min);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $leads = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json($leads);
    }

    public function show(string $id): JsonResponse
    {
        $lead = $this->leads->findById($id);

        if (!$lead) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $lead->load(['campaign', 'conversations', 'fieldValues']);

        return response()->json($lead);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'psid' => 'nullable|string',
            'name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'source' => 'required|string',
            'campaign_id' => 'nullable|uuid|exists:campaigns,id',
            'metadata' => 'nullable|array',
        ]);

        $lead = $this->leads->create($validated);

        LeadCreated::dispatch($lead, $validated['source']);

        return response()->json($lead, 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'status' => 'nullable|string',
            'score' => 'nullable|integer|min:0|max:100',
            'metadata' => 'nullable|array',
        ]);

        $lead = $this->leads->update($id, $validated);

        return response()->json($lead);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->leads->delete($id);

        return response()->json(['status' => 'deleted']);
    }

    public function addField(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'field_key' => 'required|string|max:100',
            'field_value' => 'nullable|string',
        ]);

        $this->leads->addFieldValue($id, $validated['field_key'], $validated['field_value'] ?? '');

        return response()->json(['status' => 'ok']);
    }
}
