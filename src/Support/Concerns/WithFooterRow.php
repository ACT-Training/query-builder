<?php

namespace ACTTraining\QueryBuilder\Support\Concerns;

use ACTTraining\QueryBuilder\Support\Columns\BaseColumn;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;

trait WithFooterRow
{
    public function hasFooterRow(): bool
    {
        return $this->footerColumns() !== [];
    }

    /**
     * Footer values keyed by the column's position in columns(), so two columns sharing a
     * key (e.g. two counts on `id`) keep their own values. Runs only for displayed columns
     * that have a footer.
     */
    #[Computed]
    public function footerValues(): array
    {
        $values = [];

        foreach ($this->footerColumns() as $index => $column) {
            $values[$index] = $column->footerValue($this->footerQuery());
        }

        return $values;
    }

    /**
     * @return array<int, BaseColumn> keyed by position in columns()
     */
    protected function footerColumns(): array
    {
        return collect($this->columns())
            ->filter(fn ($column) => $column instanceof BaseColumn && $column->hasFooter() && $this->isColumnDisplayed($column))
            ->all();
    }

    protected function isColumnDisplayed(BaseColumn $column): bool
    {
        return ! $column->hidden();
    }

    /**
     * A fresh copy of the filtered query for each footer value, without ordering:
     * some databases reject ORDER BY in an aggregate query.
     */
    protected function footerQuery(): Builder
    {
        return $this->rowsQuery()->withoutGlobalScope('order')->reorder();
    }
}
