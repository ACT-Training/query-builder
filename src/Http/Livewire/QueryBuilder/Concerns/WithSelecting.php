<?php

namespace ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Concerns;

trait WithSelecting
{
    public array $selectedRows = [];

    public bool $selectPage = false;

    public function updatedSelectPage($value): void
    {
        $this->selectedRows = $value
            ? $this->data()->pluck('id')->toArray()
            : [];
    }

}