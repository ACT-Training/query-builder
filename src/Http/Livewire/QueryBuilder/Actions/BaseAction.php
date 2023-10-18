<?php

/** @noinspection PhpMultipleClassDeclarationsInspection */

namespace ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Actions;

use ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Actions\Concerns\CanBeStandalone;
use ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Concerns\WithSelecting;
use Closure;
use Illuminate\Support\Enumerable;

abstract class BaseAction
{
    use CanBeStandalone;
    use WithSelecting;

    public function __construct(
        protected string $label,
        protected string $key,
        protected Closure $callback
    ) {
    }

    public static function make($label, $key, $callback): static
    {
        return new static($label, $key, $callback);
    }

    public function label(): string
    {
        return $this->label;
    }

    public function key(): string
    {
        return $this->key;
    }

    public function callback(): Closure
    {
        return $this->callback;
    }

    public function execute(Enumerable $models): mixed
    {
        return call_user_func($this->callback(), $models);
    }

    public function isAvailable(): bool
    {
        return $this->isStandalone() || count($this->selectedRows) > 0;
    }
}
