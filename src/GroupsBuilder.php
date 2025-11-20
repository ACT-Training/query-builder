<?php

/** @noinspection PhpUndefinedMethodInspection */

namespace ACTTraining\QueryBuilder;

use ACTTraining\QueryBuilder\Support\Concerns\WithGroupBuilder;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;

abstract class GroupsBuilder extends QueryBuilder
{
    use WithGroupBuilder;

    public bool $selectable = false;

    public function render(): Factory|View
    {
        return view('query-builder::group-table');
    }
}
