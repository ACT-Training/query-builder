<?php

/** @noinspection PhpUndefinedMethodInspection */

namespace ACTTraining\QueryBuilder;

use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;

/**
 * @deprecated Use ReportBuilder with $enableGroupBy = true instead.
 */
abstract class GroupsBuilder extends ReportBuilder
{
    public bool $enableGroupBy = true;

    public function render(): Factory|View
    {
        return view('query-builder::group-table');
    }
}
