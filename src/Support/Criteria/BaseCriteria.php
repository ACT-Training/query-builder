<?php

namespace ACTTraining\QueryBuilder\Support\Criteria;

use ACTTraining\QueryBuilder\Support\Contracts\CriteriaInterface;
use Illuminate\Support\Str;

class BaseCriteria implements CriteriaInterface
{
    public function apply($query): void
    {
    }

    public function applyWhereCondition($query, $field, $condition): void
    {
        if (Str::contains($field, '.')) {
            $parts = explode('.', $field);
            $columnName = array_pop($parts);
            $relationPath = implode('.', $parts);
            $query->whereHas($relationPath, function ($query) use ($columnName, $condition) {
                $condition($query, $columnName);
            });
        } else {
            $condition($query, $field);
        }
    }
}
