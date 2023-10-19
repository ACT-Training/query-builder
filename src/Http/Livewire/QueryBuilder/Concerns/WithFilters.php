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
        return [
            TextFilter::make('Name', 'full_name')->useOperator('like'),
            SelectFilter::make('Location', 'contract.location.name')->withOptions([
                'ACT Skills Centre, Caerphilly' => 'ACT Skills Centre, Caerphilly',
                'Home Based' => 'Home Based',
            ]),
            BooleanFilter::make('Line Manager', 'contract.line_manager'),
            DateFilter::make('Started on or after', 'contract.start_date')->useOperator('>='),
            DateFilter::make('Started on or before', 'contract.start_date')->useOperator('<='),
        ];
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
