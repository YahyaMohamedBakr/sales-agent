<?php

namespace App\Domains\KnowledgeBase\Repositories;

use App\Domains\KnowledgeBase\Models\KnowledgeBase;
use App\Support\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;

class KnowledgeBaseRepository extends BaseRepository implements KnowledgeBaseRepositoryInterface
{
    public function __construct(KnowledgeBase $model)
    {
        parent::__construct($model);
    }

    public function findByCategory(string $category): Collection
    {
        return $this->query()
            ->where('category', $category)
            ->orderBy('title')
            ->get();
    }

    public function search(string $query): Collection
    {
        return $this->query()
            ->where('active', true)
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                  ->orWhere('content', 'like', "%{$query}%");
            })
            ->get();
    }

    public function findActive(): Collection
    {
        return $this->query()
            ->where('active', true)
            ->orderBy('category')
            ->orderBy('title')
            ->get();
    }

    public function findActiveByCategory(string $category): Collection
    {
        return $this->query()
            ->where('active', true)
            ->where('category', $category)
            ->orderBy('title')
            ->get();
    }
}
