<?php

namespace App\Support\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface BaseRepositoryInterface
{
    public function findById(string $id): ?Model;

    public function all(): Collection;

    public function create(array $data): Model;

    public function update(string $id, array $data): Model;

    public function delete(string $id): bool;

    public function forceDelete(string $id): bool;

    public function restore(string $id): bool;
}
