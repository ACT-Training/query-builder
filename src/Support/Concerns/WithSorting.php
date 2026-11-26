<?php

namespace ACTTraining\QueryBuilder\Support\Concerns;

use ACTTraining\QueryBuilder\Support\RelationGuard;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Livewire\Attributes\Locked;

trait WithSorting
{
    use WithPagination;

    public string $sortBy = '';

    public string $sortDirection = 'asc';

    /**
     * The sort that the component sets for itself when it starts (in mount(),
     * booted() or config()). It is accepted even when it isn't a sortable column.
     */
    #[Locked]
    public string $defaultSortBy = '';

    protected bool $sortable = false;

    /**
     * Open from the moment the component is made until Livewire has booted it,
     * so only the component's own start-up code can set the default sort.
     */
    protected bool $recordingDefaultSort = true;

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
        $this->sortBy = (string) $key;
        $this->sortDirection = (string) $direction;

        if ($this->recordingDefaultSort) {
            $this->defaultSortBy = $this->sortBy;
        } else {
            $this->ensureValidSort();
        }

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

        $this->sortBy = (string) $key;
        $this->sortDirection = 'asc';

        $this->ensureValidSort();
    }

    /**
     * Livewire calls this after mount() and booted(), before it applies the
     * browser's changes and calls its actions.
     */
    public function bootedWithSorting(): void
    {
        $this->recordingDefaultSort = false;
    }

    public function updatedWithSorting(string $property): void
    {
        if (in_array($property, ['sortBy', 'sortDirection'], true)) {
            $this->ensureValidSort();
        }
    }

    /**
     * The keys the table may sort by: its sortable columns and its default sort.
     */
    protected function allowedSortKeys(): array
    {
        // Columns use this trait too, and have no columns of their own.
        $keys = method_exists($this, 'sortableColumnKeys') ? $this->sortableColumnKeys() : [];

        if ($this->defaultSortBy !== '') {
            $keys[] = $this->defaultSortBy;
        }

        return $keys;
    }

    /**
     * Clears a sort that the table doesn't allow, and any direction other than
     * asc or desc. The sort comes from the browser, so call this before you use
     * $sortBy or $sortDirection in a query of your own.
     */
    public function ensureValidSort(): void
    {
        $direction = strtolower($this->sortDirection);
        $this->sortDirection = in_array($direction, ['asc', 'desc'], true) ? $direction : 'asc';

        if ($this->sortBy !== '' && ! in_array($this->sortBy, $this->allowedSortKeys(), true)) {
            $this->sortBy = '';
            $this->sortDirection = 'asc';
        }
    }

    protected function applySorting(Builder $query): void
    {
        $this->ensureValidSort();

        if ($this->sortBy === '') {
            return;
        }

        if (Str::contains($this->sortBy, '.')) {
            $this->orderByRelated($query, $this->sortBy, $this->sortDirection);
        } else {
            $query->orderBy($this->sortBy, $this->sortDirection);
        }
    }

    protected function orderByRelated(Builder $query, string $relationColumn, string $direction = 'asc'): void
    {
        // Ensure only one dot is present
        if (substr_count($relationColumn, '.') !== 1) {
            throw new InvalidArgumentException("Invalid relation column: '{$relationColumn}'. Only single-level relationships are supported.");
        }

        [$relation, $column] = explode('.', $relationColumn, 2);

        $model = $query->getModel();

        // Not a relationship, so treat the key as a column qualified with its table name.
        if (! RelationGuard::isRelation($model, $relation)) {
            $query->orderBy($relationColumn, $direction);

            return;
        }

        $relationQuery = $model->{$relation}();

        if (! $relationQuery instanceof Relation) {
            return;
        }

        $relatedModel = $relationQuery->getRelated();

        $query->orderBy(
            $relatedModel::select($column)
                ->whereColumn(
                    "{$relatedModel->getTable()}.id",
                    $relationQuery->getForeignKeyName() /* @phpstan-ignore method.notFound */
                ),
            $direction
        );
    }
}
