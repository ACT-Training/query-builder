<?php

namespace ACTTraining\QueryBuilder\Support\Columns;

use ACTTraining\QueryBuilder\Support\Concerns\WithSearch;
use ACTTraining\QueryBuilder\Support\Concerns\WithSorting;
use Illuminate\Support\Str;

/** @phpstan-consistent-constructor */
class BaseColumn
{
    use WithSearch;
    use WithSorting;

    public string $key;

    public string $label;

    public bool $showHeader = true;

    public string $justify = 'justify-normal';

    public string $component = 'columns.column';

    public bool $hideFromSelector = false;

    public bool $isSearchable = false;

    private bool $isHidden = false;

    private $subTitle = null;

    private $reformatCallback = null;

    public function __construct($key, $label)
    {
        $this->key = $key;
        $this->label = $label;
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

    public function hideIf(callable $condition): static
    {
        $this->isHidden = $condition();

        return $this;
    }

    public function hidden(): bool
    {
        return $this->isHidden;

    }

    public function withSubTitle(callable $condition): static
    {
        $this->subTitle = $condition;

        return $this;
    }

    public function subTitle($row): string
    {
        return call_user_func($this->subTitle, $row);
    }

    public function hasSubTitle(): bool
    {
        return is_callable($this->subTitle);
    }

    public function hideHeader(): static
    {
        $this->showHeader = false;

        return $this;
    }

    public function hideFromSelector(): static
    {
        $this->hideFromSelector = true;

        return $this;
    }

    public function justify($where): static
    {
        $this->justify = match ($where) {
            'center' => 'justify-center text-center',
            'left' => 'justify-start text-left',
            'right' => 'justify-end text-right',
            default => 'justify-normal',
        };

        return $this;
    }

    public function reformatUsing(callable $callback): static
    {
        $this->reformatCallback = $callback;

        return $this;
    }

    public function getValue($row)
    {
        ray($row);

        $value = data_get($row, $this->key);
        if (is_callable($this->reformatCallback)) {
            return call_user_func($this->reformatCallback, $value, $row, $this);
        }

        return $value;
    }

}
