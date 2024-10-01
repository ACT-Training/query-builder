<?php

/** @noinspection PhpUndefinedMethodInspection */

namespace ACTTraining\QueryBuilder;

use ACTTraining\QueryBuilder\Support\Concerns\WithReportBuilder;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;

abstract class ReportBuilder extends QueryBuilder
{
    use WithReportBuilder;

    public bool $selectable = false;

    public function render(): Factory|View
    {
        return view('query-builder::report-table');
    }
}
