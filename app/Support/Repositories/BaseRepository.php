<?php

namespace App\Support\Repositories;

use App\Support\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

abstract class BaseRepository implements BaseRepositoryInterface
{
    protected Model $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function findById(string $id): ?Model
    {
        return $this->model->find($id);
    }

    public function all(): Collection
    {
        return $this->model->all();
    }

    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    public function update(string $id, array $data): Model
    {
        $record = $this->findById($id);

        if (!$record) {
            throw new \RuntimeException("Record not found: {$id}");
        }

        $record->update($data);

        return $record->fresh();
    }

    public function delete(string $id): bool
    {
        $record = $this->findById($id);

        if (!$record) {
            return false;
        }

        return $record->delete();
    }

    public function forceDelete(string $id): bool
    {
        $record = $this->model->withTrashed()->find($id);

        if (!$record) {
            return false;
        }

        return $record->forceDelete();
    }

    public function restore(string $id): bool
    {
        $record = $this->model->withTrashed()->find($id);

        if (!$record) {
            return false;
        }

        return $record->restore();
    }

    protected function query(): Builder
    {
        return $this->model->newQuery();
    }
}
