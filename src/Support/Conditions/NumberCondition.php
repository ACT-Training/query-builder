<?php

namespace ACTTraining\QueryBuilder\Support\Conditions;

class NumberCondition extends BaseCondition
{
    public string $inputType = 'number';

    public function operations(): array
    {
        return [
            'equals' => 'equals',
            'not_equals' => 'is not equal to',
            'greater_than' => 'is greater than',
            'less_than' => 'is less than',
            'greater_than_or_equal' => 'is greater than or equal to',
            'less_than_or_equal' => 'is less than or equal to',
            'is_set' => 'is set',
            'is_not_set' => 'is not set',
        ];
    }
}
