<?php

namespace ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Concerns;

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
}
