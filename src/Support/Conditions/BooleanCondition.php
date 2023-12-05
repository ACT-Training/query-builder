<?php

namespace ACTTraining\QueryBuilder\Support\Conditions;

class BooleanCondition extends BaseCondition
{
    public string $inputType = 'boolean';

    protected bool $displayValue = false;

    public function operations(): array
    {
        return [
            'is_true' => 'is true',
            'is_false' => 'is false',
        ];
    }
}
