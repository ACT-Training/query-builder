<?php

namespace ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Criteria;

use ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Contracts\CriteriaInterface;

class NullCriteria extends BaseCriteria implements CriteriaInterface
{
    public string $inputType = 'boolean';

    private $field;

    private $operation;

    public function __construct(string $field, string $operation)
    {
        $this->field = $field;
        $this->operation = $operation;
    }

    public function apply($query): void
    {
        $this->applyWhereCondition($query, $this->field, function ($query, $field) {
            if ($this->operation === 'is_not_set') {
                $query->whereNull($field);
            } else {
                $query->whereNotNull($field);
            }
        });
    }
}
