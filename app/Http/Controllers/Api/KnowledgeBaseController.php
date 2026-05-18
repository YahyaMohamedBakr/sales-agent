<?php

namespace App\Http\Controllers\Api;

use App\Domains\KnowledgeBase\Models\KnowledgeBase;
use App\Domains\KnowledgeBase\Repositories\KnowledgeBaseRepositoryInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KnowledgeBaseController extends Controller
{
    public function __construct(
        private KnowledgeBaseRepositoryInterface $kb,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = KnowledgeBase::query();

        if ($category = $request->input('category')) {
            $query->where('category', $category);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        return response()->json($query->orderBy('category')->orderBy('title')->paginate(20));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category' => 'required|string|max:100',
            'title' => 'required|string',
            'content' => 'required|string',
            'active' => 'nullable|boolean',
        ]);

        $doc = $this->kb->create($validated);

        return response()->json($doc, 201);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json($this->kb->findById($id));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'category' => 'nullable|string|max:100',
            'title' => 'nullable|string',
            'content' => 'nullable|string',
            'active' => 'nullable|boolean',
        ]);

        return response()->json($this->kb->update($id, $validated));
    }

    public function destroy(string $id): JsonResponse
    {
        $this->kb->delete($id);

        return response()->json(['status' => 'deleted']);
    }

    public function categories(): JsonResponse
    {
        $categories = KnowledgeBase::select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return response()->json($categories);
    }
}
