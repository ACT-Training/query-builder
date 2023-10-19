<?php

namespace ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Filters;

class TextFilter extends BaseFilter
{
    public function parseValue($value)
    {
        if ($this->operator() === 'like') {
            return "%{$value}%";
        }

        return $value;
    }
}
