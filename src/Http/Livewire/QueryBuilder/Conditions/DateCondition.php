<?php

namespace ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Conditions;

class DateCondition extends BaseCondition
{
    public string $inputType = 'date';

    public function operations(): array
    {
        return [
            'is' => 'is on',
            'is_not' => 'is not on',
            'is_before' => 'is before',
            'is_on_or_before' => 'is on or before',
            'is_after' => 'is after',
            'is_on_or_after' => 'is on or after',
            'is_between' => 'is between',
            'is_not_between' => 'is not between',
            'is_set' => 'is set',
            'is_not_set' => 'is not set',
        ];
    }
}
