<?php

namespace ACTTraining\QueryBuilder\Support\Conditions;

use Illuminate\Support\Str;

/** @phpstan-consistent-constructor */
class BaseCondition
{
    public string $key;

    public string $label;

    public array $operations = [];

    public string $inputType = 'text';

    protected bool $displayValue = true;

    public function __construct($key, $label)
    {
        $this->key = $key;
        $this->label = $label;
        $this->operations = $this->operations();
    }

    public static function make($label, $key = null): static
    {
        if (is_null($key)) {
            $key = Str::snake($label);
        }

        return new static($key, $label);
    }

    public function operations(): array
    {
        return [
            'equals' => 'equals',
            'not_equals' => 'does not equal',
            'contains' => 'contains',
            'not_contains' => 'does not contain',
            'starts_with' => 'starts with',
            'ends_with' => 'ends with',
            'is_set' => 'is set',
            'is_not_set' => 'is not set',
        ];
    }

    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'operations' => $this->operations,
        ];
    }

    public function displayValue(): bool
    {
        return $this->displayValue;
    }

    public function inputType(): string
    {
        return $this->inputType;
    }
}
