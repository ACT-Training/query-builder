<?php

namespace ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Actions\Concerns;

trait CanBeStandalone
{
    protected bool $standalone = false;

    public function standalone(bool $standalone = true): static
    {
        $this->standalone = $standalone;

        return $this;
    }

    public function isStandalone(): bool
    {
        return $this->standalone;
    }
}
