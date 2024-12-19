<?php

namespace ACTTraining\QueryBuilder\Support\Criteria;

use ACTTraining\QueryBuilder\Support\Contracts\CriteriaInterface;

class CompareCriteria extends BaseCriteria implements CriteriaInterface
{
    private $field;

    private $value;

    private $operator;

    public function __construct(string $field, string $value, string $operator = '=')
    {
        $this->field = $field;
        $this->value = $value;
        $this->operator = $operator;
    }

    public function apply($query): void
    {
        $this->applyWhereCondition($query, $this->field, function ($query, $field) {
            $query->where($field, $this->operator, $this->value);
        });
    }
}
