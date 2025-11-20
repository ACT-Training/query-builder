<?php

namespace ACTTraining\QueryBuilder\Support\Concerns;

use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

trait WithSorting
{
    use WithPagination;

    public string $sortBy = '';

    public string $sortDirection = 'asc';

    protected bool $sortable = false;

    public function isSortable(): bool
    {
        return $this->sortable;
    }

    public function sortable(bool $sortable = true): static
    {
        $this->sortable = $sortable;

        return $this;
    }

    public function setSort($key, $direction = 'asc'): static
    {
        $this->sortBy = $key;
        $this->sortDirection = $direction;

        return $this;
    }

    public function sort($key): void
    {
        $this->resetPage();

        if ($this->sortBy === $key) {

            $direction = match ($this->sortDirection) {
                'asc' => 'desc',
                'desc' , => null,
                default => 'asc',
            };

            if ($direction === null) {
                $this->sortDirection = 'asc';
                $this->sortBy = '';
            } else {
                $this->sortDirection = $direction;
            }

            return;
        }

        $this->sortBy = $key;
        $this->sortDirection = 'asc';
    }

    protected function orderByRelated(Builder $query, string $relationColumn, string $direction = 'asc'): void
    {
        // Ensure only one dot is present
        if (substr_count($relationColumn, '.') !== 1) {
            throw new InvalidArgumentException("Invalid relation column: '{$relationColumn}'. Only single-level relationships are supported.");
        }

        [$relation, $column] = explode('.', $relationColumn, 2);

        // Get the related model instance correctly
        $relatedModel = $query->getModel()->{$relation}()->getRelated();

        $query->orderBy(
            $relatedModel::select($column)
                ->whereColumn(
                    "{$relatedModel->getTable()}.id",
                    $query->getModel()->{$relation}()->getForeignKeyName()
                ),
            $direction
        );
    }
}
