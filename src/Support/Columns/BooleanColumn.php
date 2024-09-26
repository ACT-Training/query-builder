<?php

namespace ACTTraining\QueryBuilder\Support\Columns;

class BooleanColumn extends BaseColumn
{
    public string $component = 'columns.boolean-column';

    protected bool|null $hideCondition = null;

    public function hideIf($condition = null): static
    {
        $this->hideCondition = $condition;

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
