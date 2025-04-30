<?php

namespace ACTTraining\QueryBuilder\Support\Criteria;

use ACTTraining\QueryBuilder\Support\Contracts\CriteriaInterface;

class NullCriteria extends BaseCriteria implements CriteriaInterface
{
    public string $inputType = 'boolean';

    private string $field;

    private string $operation;

    public function __construct(string $field, string $operation)
    {
        $this->field = $field;
        $this->operation = $operation;
    }

    public function apply($query): void
    {
        $this->applyWhereCondition($query, $this->field, function ($query, $field) {
            if ($this->operation === 'is_not_set') {
                $query->whereNull($field)
                    ->orWhere($field, '')
                    ->orWhereJsonLength($field, 0);
            } else {
                $query->whereNotNull($field)
                    ->where($field, '!=', '')
                    ->whereJsonLength($field, '>', 0);
            }
        });
    }
}
