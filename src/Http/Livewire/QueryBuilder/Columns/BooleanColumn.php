<?php

namespace ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Columns;

class BooleanColumn extends BaseColumn
{
    public string $component = 'columns.boolean-column';

    protected $hideCondition = null;

    public function hideIf($value): static
    {
        $this->hideCondition = $value;

        return $this;
    }

    public function hideTrue(): bool
    {
        return $this->hideCondition === true;
    }

    public function hideFalse(): bool
    {
        return $this->hideCondition === false;
    }
}
