<?php

namespace ACTTraining\QueryBuilder\Support\Concerns;

trait WithFilters
{
    protected bool $displayFilters = false;

    public array $filterValues = [];

    public function areFiltersAvailable(): bool
    {
        return $this->displayFilters && count($this->filters()) > 0;
    }

    public function displayFilters(bool $displayFilters = true): static
    {
        $this->displayFilters = $displayFilters;

        return $this;
    }

    public function filters(): array
    {
        return [];
    }

    public function resetFilters(): void
    {
        foreach ($this->filters() as $filter) {
            $this->filterValues[$filter->code()] = null;
        }
        $this->resetPage();
    }

    public function updatedFilterValues(): void
    {
        $this->resetPage();
    }

    public function isFiltered(): bool
    {
        $filtered = false;
        foreach ($this->filterValues as $filterValue) {
            if ($filterValue !== null) {
                $filtered = true;
                break;
            }
        }

        return $filtered;
    }
}
