<?php

/** @noinspection PhpMissingParentConstructorInspection */

namespace ACTTraining\QueryBuilder\Support\Columns;

class ViewColumn extends BaseColumn
{
    public string $component = 'columns.view-column';

    public function getValue($row): null
    {
        return null;
    }
}
