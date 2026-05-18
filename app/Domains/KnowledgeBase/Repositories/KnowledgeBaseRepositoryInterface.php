<?php

namespace App\Domains\KnowledgeBase\Repositories;

use App\Domains\KnowledgeBase\Models\KnowledgeBase;
use App\Support\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

interface KnowledgeBaseRepositoryInterface extends BaseRepositoryInterface
{
    public function findByCategory(string $category): Collection;

    public function search(string $query): Collection;

    public function findActive(): Collection;

    public function findActiveByCategory(string $category): Collection;
}
