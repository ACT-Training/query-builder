<?php

/** @noinspection PhpUndefinedMethodInspection */

namespace ACTTraining\QueryBuilder;

use ACTTraining\QueryBuilder\Support\Concerns\WithReportBuilder;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;

abstract class ReportBuilder extends QueryBuilder
{
    use WithReportBuilder;

    public bool $selectable = false;

    #[Computed]
    public function rowsQuery()
    {
        $query = parent::rowsQuery();

        if (! $this->hasGroupBy()) {
            return $query;
        }

        if (! in_array($this->aggregateFunction, $this->aggregateFunctions(), true)) {
            return $query;
        }

        $query->selectRaw("{$this->groupBy}, {$this->aggregateFunction}({$this->aggregateColumn}) as aggregate")
            ->groupBy($this->groupBy);

        return $query;
    }

    public function render(): Factory|View
    {
        return view('query-builder::report-table');
    }
}
