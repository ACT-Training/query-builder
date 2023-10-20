<?php

namespace ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Concerns;

use ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Filters\BooleanFilter;
use ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Filters\DateFilter;
use ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Filters\SelectFilter;
use ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Filters\TextFilter;

trait WithFilters
{
    protected $displayFilters = false;

    public $filterValues = [];

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
        $this->filterValues = [];
        $this->resetPage();
    }

    public function updatedFilterValues(): void
    {
        $this->resetPage();
    }
}
