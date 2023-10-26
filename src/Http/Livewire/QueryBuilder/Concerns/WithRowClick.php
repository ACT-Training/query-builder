<?php

namespace ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Concerns;

use Blade;

trait WithRowClick
{
    protected $rowClickable = true;

    public function isClickable(): bool
    {
        return $this->rowClickable;
    }

    public function rowClickable(bool $rowClickable = true): static
    {
        $this->rowClickable = $rowClickable;

        return $this;
    }

    public function rowClick(int $row): void
    {
        //
    }

    public function renderRowClick($row): string
    {
        return 'wire:click="rowClick(' . $row . ')"';
    }
}
