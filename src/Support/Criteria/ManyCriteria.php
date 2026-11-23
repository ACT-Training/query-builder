<?php

namespace ACTTraining\QueryBuilder\Support\Criteria;

use ACTTraining\QueryBuilder\Support\Contracts\CriteriaInterface;
use Illuminate\Support\Str;

/**
 * "Is any of" and "is none of": the field has, or hasn't, any of several values. On a related
 * record (a dot in the field), "is none of" keeps the rows with no related record at all.
 */
class ManyCriteria extends BaseCriteria implements CriteriaInterface
{
    /**
     * @param  list<string>  $values
     */
    public function __construct(private string $field, private array $values, private bool $none = false) {}

    public function apply($query): void
    {
        if (! $this->none) {
            $this->applyWhereCondition($query, $this->field, function ($query, $field) {
                $query->whereIn($query->qualifyColumn($field), $this->values);
            });

            return;
        }

        if (! Str::contains($this->field, '.')) {
            $query->whereNotIn($this->field, $this->values);

            return;
        }

        $query->whereDoesntHave(Str::beforeLast($this->field, '.'), function ($query) {
            $query->whereIn($query->qualifyColumn(Str::afterLast($this->field, '.')), $this->values);
        });
    }
}
