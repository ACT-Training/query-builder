<?php

namespace ACTTraining\QueryBuilder\Support\Filters;

use Carbon\Carbon;

class DateFilter extends BaseFilter
{
    public string $component = 'date';

    public function parseValue($value)
    {
        return Carbon::parse($value)->startOfDay();
    }
}
