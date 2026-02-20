<?php

namespace ACTTraining\QueryBuilder\Support\Filters;

use Illuminate\Support\Str;

/** @phpstan-consistent-constructor */
class BaseFilter
{
    public string $key;

    public string $label;

    public string $operator = '=';

    public string $code = '';

    public string $component = 'text';

    public function __construct($key, $label)
    {
        $this->key = $key;
        $this->label = $label;
        $this->code = $this->buildCode();
    }

    public static function make($label, $key = null): static
    {
        if (is_null($key)) {
            $key = Str::snake($label);
        }

        return new static($key, $label);
    }

    public function label(): string
    {
        return $this->label;
    }

    public function key(): string
    {
        return $this->key;
    }

    public function operator(): string
    {
        return $this->operator;
    }

    public function code(): string
    {
        return $this->code;
    }

    public function component(): string
    {
        return $this->component;
    }

    public function useOperator(string $operator): static
    {
        $this->operator = $operator;
        $this->code = $this->buildCode();

        return $this;
    }

    /**
     * Build the filter code from the label and a Blade-safe operator alias.
     *
     * Operators like >= and <= are mapped to gte/lte so that Blade's {{ }}
     * HTML-encoding does not break wire:model bindings.
     */
    private function buildCode(): string
    {
        $operator = match ($this->operator) {
            '>=' => 'gte',
            '<=' => 'lte',
            '>' => 'gt',
            '<' => 'lt',
            default => $this->operator,
        };

        return $this->label.':'.$operator;
    }

    public function useComponent(string $component): static
    {
        $this->component = $component;

        return $this;
    }

    public function parseValue($value)
    {
        return $value;
    }
}
