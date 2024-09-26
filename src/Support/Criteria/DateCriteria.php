<?php /** @noinspection PhpMultipleClassDeclarationsInspection */

namespace ACTTraining\QueryBuilder\Support\Criteria;

use ACTTraining\QueryBuilder\Support\Contracts\CriteriaInterface;
use Carbon\Carbon;
use InvalidArgumentException;

class DateCriteria extends BaseCriteria implements CriteriaInterface
{
    private string $field;

    private string|array $value;

    private string $operator;

    public function __construct(string $field, string|array $value, string $operator = '=')
    {
        // Check if the date is in the 'd/m/Y' format and convert it to 'd-m-Y'
        $date = \DateTime::createFromFormat('d/m/Y', $field);

        if ($date) {
            // If the date is successfully parsed in the 'd/m/Y' format, convert it to 'd-m-Y'
            $this->field = $date->format('d-m-Y');
        } else {
            // If the date was not in 'd/m/Y', assume it's already in 'd-m-Y' format
            // Optionally, you could check again if it's in the correct format
            $date = \DateTime::createFromFormat('d-m-Y', $field);

            if ($date) {
                $this->field = $field; // Already in 'd-m-Y', so just assign it
            }
        }

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
