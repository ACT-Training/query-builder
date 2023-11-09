<?php

/** @noinspection PhpMissingParentConstructorInspection */

namespace ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Columns;

class ViewColumn extends BaseColumn
{
    public string $component = 'columns.view-column';

    public function getValue($row)
    {
        return null;
    }
}
