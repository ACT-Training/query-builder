<?php

namespace ACTTraining\QueryBuilder\Support\Criteria;

use ACTTraining\QueryBuilder\Support\Contracts\CriteriaInterface;
use Illuminate\Support\Str;

/**
 * "Is any of" and "is none of": the field has, or hasn't, any of several values. A plain column is
 * named as given, like the other criteria. On a related record (a dot in the field), the column is
 * qualified with the related table, and "is none of" keeps the rows with no related record at all.
 */
class ManyCriteria extends BaseCriteria implements CriteriaInterface
{
    /**
     * @param  list<string>  $values
     */
    public function __construct(private string $field, private array $values, private bool $none = false) {}

    public function apply($query): void
    {
        if (! Str::contains($this->field, '.')) {
            $query->{$this->none ? 'whereNotIn' : 'whereIn'}($this->field, $this->values);

            return;
        }

        $relation = Str::beforeLast($this->field, '.');
        $hasAny = function ($query) {
            $query->whereIn($query->qualifyColumn(Str::afterLast($this->field, '.')), $this->values);
        };

        $query->{$this->none ? 'whereDoesntHave' : 'whereHas'}($relation, $hasAny);
    }
}
