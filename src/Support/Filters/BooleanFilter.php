<?php

namespace ACTTraining\QueryBuilder\Support\Filters;

class BooleanFilter extends BaseFilter
{
    public string $component = 'boolean';

    public function parseValue($value)
    {
        if (is_null($value) || $value === '') {
            return null;
        }

        if (is_string($value)) {
            $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }

        return $value;
    }
}
