<?php

namespace ACTTraining\QueryBuilder\Support\Concerns;

trait WithRowClick
{
    protected bool $rowClickable = true;

    public function isClickable(): bool
    {
        return $this->rowClickable;
    }

    public function rowClickable(bool $rowClickable = true): static
    {
        $this->rowClickable = $rowClickable;

        return $this;
    }

    public function rowClick($row): void
    {
        //
    }

    public function renderRowClick($row): string
    {
        // Check if $row is a string and quote it appropriately
        if (is_string($row)) {
            $row = "'".$row."'";
        }

        return 'wire:click="rowClick('.$row.')"';
    }
}
