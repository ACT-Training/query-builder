<?php

namespace ACTTraining\QueryBuilder\Support\Actions\Concerns;

trait CanLoadLazily
{
    protected bool $lazy = false;

    protected int $lazyChunkSize = 500;

    /**
     * Hand the callback a LazyCollection that loads the selected rows in chunks,
     * in key order, instead of an Eloquent Collection holding every row at once.
     * Use it for actions that may run on thousands of rows.
     */
    public function lazy(int $chunkSize = 500): static
    {
        $this->lazy = true;
        $this->lazyChunkSize = $chunkSize;

        return $this;
    }

    public function isLazy(): bool
    {
        return $this->lazy;
    }

    public function lazyChunkSize(): int
    {
        return $this->lazyChunkSize;
    }
}
