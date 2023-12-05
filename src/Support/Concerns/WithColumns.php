<?php

namespace ACTTraining\QueryBuilder\Support\Concerns;

use Illuminate\Support\Enumerable;

trait WithColumns
{
    public $justify = 'justify-center';

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

        return $this->resolveColumns()->pluck('key')->toArray();
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
