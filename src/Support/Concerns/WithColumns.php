<?php

namespace ACTTraining\QueryBuilder\Support\Concerns;

use Illuminate\Support\Enumerable;

trait WithColumns
{
    public string $justify = 'justify-center';

    public function columnOptions(): array
    {
        return $this->resolveColumns()->pluck('label', 'key')->toArray();
    }

    public function columns(): array
    {
        return [];
    }

    public function columnsForSelector(): array
    {
        return $this->resolveColumns()->pluck('label', 'key')->toArray();
    }

    public function allColumns(): array
    {
        $this->useCachedRows();

        $keys = $this->resolveColumns()->pluck('key')->toArray();

        // The column chooser's Reset button calls this. QueryBuilder used to show
        // every column again on the next request anyway; with saved views it
        // keeps the chosen columns, so reset them here.
        if (property_exists($this, 'displayColumns')) {
            $this->displayColumns = $keys;
        }

        return $keys;
    }

    public function updatedDisplayColumns(): void
    {
        $this->useCachedRows();
    }

    protected function resolveColumns(): Enumerable
    {
        return collect($this->columns());
    }
}
