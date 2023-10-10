<?php

namespace ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Columns;

use ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Concerns\WithSorting;
use Illuminate\Support\Str;

class BaseColumn
{
    use WithSorting;

    public string $key;

    public string $label;

    public bool $showHeader = true;

    public string $justify = 'justify-normal';

    public string $component = 'columns.column';

    protected string $code;

    public function __construct($key, $label)
    {
        $this->key = $key;
        $this->label = $label;
        $this->code = $code ?? Str::of($key)->replace('.', '_')->toString();
    }

    public static function make($label, $key = null): static
    {
        if (is_null($key)) {
            $key = Str::snake($label);
        }

        return new static($key, $label);
    }

    public function component($component): static
    {
        $this->component = $component;

        return $this;
    }

    public function hideHeader(): static
    {
        $this->showHeader = false;

        return $this;
    }

    public function justify($where): static
    {
        $this->justify = match ($where) {
            'center' => 'justify-center',
            'left' => 'justify-start',
            'right' => 'justify-end',
            default => 'justify-normal',
        };

        return $this;
    }

    public function getValue($row)
    {
        return data_get($row, $this->key);
    }
}
