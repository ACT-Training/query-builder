<?php

/** @noinspection PhpMissingParentConstructorInspection */

namespace ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Columns;

class ViewColumn extends BaseColumn
{

    public string $component = 'columns.view-column';

    public function __construct($key, $label)
    {
        $this->key = $key;
        $this->label = $label;
    }

    public static function make($label, $key = null): static
    {
        $key = null;

        return new static($key, $label);
    }

}
