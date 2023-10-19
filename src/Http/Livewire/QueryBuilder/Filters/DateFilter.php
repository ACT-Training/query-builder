<?php

namespace ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Filters;

use Carbon\Carbon;

class DateFilter extends BaseFilter
{
    public string $component = 'date';

    public function parseValue($value)
    {
        return Carbon::parse($value)->startOfDay();
    }
}
