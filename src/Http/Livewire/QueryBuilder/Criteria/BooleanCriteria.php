<?php

namespace ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Criteria;

use ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Contracts\CriteriaInterface;

class BooleanCriteria extends BaseCriteria implements CriteriaInterface
{
    public string $inputType = 'boolean';

    private $field;

    private $value;

    public function __construct(string $field, bool $value)
    {
        $this->field = $field;
        $this->value = $value;
    }

    public function apply($query): void
    {
        $this->applyWhereCondition($query, $this->field, function ($query, $field) {
            $query->where($field, $this->value);
        });
    }
}
