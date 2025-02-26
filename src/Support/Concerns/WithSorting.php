<?php

namespace ACTTraining\QueryBuilder\Support\Concerns;

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
                'desc' ,=> null,
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
}
