<?php

namespace ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Criteria;

use ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Contracts\CriteriaInterface;
use Carbon\Carbon;

class DateCriteria extends BaseCriteria implements CriteriaInterface
{
    private $field;

    private $value;

    private $operator;

    public function __construct(string $field, string|array $value, string $operator = '=')
    {
        $this->field = $field;
        $this->value = $value;
        $this->operator = $operator;
    }

    public function apply($query): void
    {
        if (! is_array($this->value)) {
            $date = Carbon::createFromFormat('d-m-Y', $this->value);

            $this->applyWhereCondition($query, $this->field, function ($query, $field) use ($date) {
                $query->whereDate($field, $this->operator, $date);
            });
        } else {
            if ($this->value[0] && ! $this->value[1]) {
                $date = Carbon::createFromFormat('d-m-Y', $this->value[0]);

                $this->applyWhereCondition($query, $this->field, function ($query, $field) use ($date) {
                    $query->whereDate($field, '>', $date);
                });
            }
            if ($this->value[0] && $this->value[1]) {
                $date1 = Carbon::createFromFormat('d-m-Y', $this->value[0]);
                $date2 = Carbon::createFromFormat('d-m-Y', $this->value[1]);

                $this->applyWhereCondition($query, $this->field, function ($query, $field) use ($date1, $date2) {
                    if ($this->operator === 'between') {
                        $query->whereBetween($field, [$date1, $date2]);
                    } else {
                        $query->whereNotBetween($field, [$date1, $date2]);
                    }
                });
            }
        }
    }
}
