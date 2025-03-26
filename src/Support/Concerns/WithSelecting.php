<?php

namespace ACTTraining\QueryBuilder\Support\Concerns;

trait WithSelecting
{
    public bool $selectable = true;

    public array $selectedRows = [];

    public bool $selectPage = false;

    public bool $selectAll = false;

    public function updatedSelectPage($value): void
    {
        $this->selectedRows = $value
            ? $this->rows->pluck('id')->toArray()
            : [];

        if ($this->selectAll && ! $value) {
            $this->selectAll = false;
        }

        $this->dispatch('refreshTable')->self();
    }

    public function updatedSelectedRows(): void
    {
        $this->selectAll = false;
        $this->selectPage = false;
        $this->dispatch('refreshTable')->self();
    }

    public function selectAll(): void
    {
        $this->selectAll = ! $this->selectAll;
        $this->dispatch('refreshTable')->self();
    }

    public function clearSelection(): void
    {
        $this->selectAll = false;
        $this->selectPage = false;
        $this->selectedRows = [];

        $this->dispatch('refreshTable')->self();

    }
}
