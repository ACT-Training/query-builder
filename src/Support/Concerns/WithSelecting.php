<?php

namespace ACTTraining\QueryBuilder\Support\Concerns;

use Illuminate\Support\Str;

trait WithSelecting
{
    public bool $selectable = true;

    public array $selectedRows = [];

    public bool $selectPage = false;

    public bool $selectAll = false;

    public function updatedSelectPage($value): void
    {
        $this->selectedRows = $value
            ? $this->pageRowKeys()
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

    /**
     * A selection belongs to the rows it was made from. Once the search, a filter
     * or the criteria change, it would act on a different set of rows than the
     * person chose, so it's dropped. (Livewire calls this for every property the
     * browser updates.)
     */
    public function updatedWithSelecting(string $property): void
    {
        if (in_array(Str::before($property, '.'), ['searchBy', 'filterValues', 'criteria', 'andOr'], true)) {
            $this->resetSelection();
        }
    }

    public function toggleSelectAll(): void
    {
        $this->selectAll = ! $this->selectAll;

        $this->selectedRows = $this->selectAll
            ? (clone $this->rowsQuery)->reorder()->pluck($this->rowsQuery->getModel()->getQualifiedKeyName())->all() /* @phpstan-ignore-line */
            : [];
        $this->dispatch('refreshTable')->self();
    }

    /**
     * How many rows a bulk action would act on: every row the search, filters
     * and criteria leave with "Select all", or the ticked rows.
     */
    public function selectedRowsCount(): int
    {
        return $this->selectAll
            ? $this->rowsQuery->count() /* @phpstan-ignore-line */
            : count($this->selectedRows);
    }

    public function clearSelection(): void
    {
        $this->resetSelection();

        $this->dispatch('refreshTable')->self();
    }

    protected function resetSelection(): void
    {
        $this->selectAll = false;
        $this->selectPage = false;
        $this->selectedRows = [];
    }

    /**
     * The keys of the rows on the current page, by the model's own key name.
     */
    protected function pageRowKeys(): array
    {
        return $this->rows->modelKeys(); /* @phpstan-ignore-line */
    }
}
