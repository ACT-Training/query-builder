<?php

namespace ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Concerns;

trait WithSelecting
{
    public array $selectedRows = [];

    public bool $selectPage = false;

    public bool $selectAll = false;

    public function updatedSelectPage($value): void
    {
        $this->selectedRows = $value
            ? $this->data()->pluck('id')->toArray()
            : [];

        if ($this->selectAll && ! $value) {
            $this->selectAll = false;
        }
    }

    public function updatedSelectedRows(): void
    {
        $this->selectAll = false;
        $this->selectPage = false;
    }

    public function selectAll(): void
    {
        $this->selectAll = ! $this->selectAll;
    }
}
