<?php

namespace ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Concerns;

use Illuminate\Support\Facades\Blade;

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
        return Blade::render(
            'wire:click="rowClick({{ $row }})"',
            ['row' => $row]
        );
    }
}
