<?php

namespace ACTTraining\QueryBuilder\Support\Conditions;

class NullCondition extends BaseCondition
{
    public string $inputType = 'enum';

    public function operations(): array
    {
        return [
            'is_set' => 'is set',
            'is_not_set' => 'is not set',
        ];
    }
}
