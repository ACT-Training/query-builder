<?php

namespace ACTTraining\QueryBuilder\Support\Conditions;

class NullCondition extends BaseCondition
{
    public string $inputType = 'number';

    public function operations(): array
    {
        return [
            'is_set' => 'is set',
            'is_not_set' => 'is not set',
        ];
    }
}
