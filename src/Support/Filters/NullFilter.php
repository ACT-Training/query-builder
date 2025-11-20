<?php

namespace ACTTraining\QueryBuilder\Support\Filters;

class NullFilter extends BaseFilter
{
    public string $operator = '';

    public string $component = 'null';

    private string $prompt = 'Select an option';

    public function options(): array
    {
        return [
            'isSet' => 'Is set',
            'isNotSet' => 'Is not set',
        ];
    }

    public function prompt(): string
    {
        return $this->prompt;
    }

    public function apply($query, $value)
    {
        $key = $this->key();
        $isNegation = $value === 'isNotSet';

        return $query->where(function ($q) use ($key, $isNegation) {
            if ($isNegation) {
                $q->whereNull($key)
                    ->orWhere($key, '')
                    ->orWhereJsonLength($key, 0);
            } else {
                $q->whereNotNull($key)
                    ->where($key, '!=', '')
                    ->whereJsonLength($key, '>', 0);
            }
        });
    }
}
