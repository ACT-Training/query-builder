<?php

namespace ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Concerns;

trait WithToolbar
{
    protected $displayToolbar = true;

    protected $displayRowSelector = true;

    protected $displayColumnSelector = true;

    public function displayRowSelector(bool $displayRowSelector = true): static
    {
        $this->displayRowSelector = $displayRowSelector;

        return $this;
    }

    public function displayToolbar(bool $displayToolbar = true): static
    {
        $this->displayToolbar = $displayToolbar;

        return $this;
    }

    public function displayColumnSelector(bool $displayColumnSelector = true): static
    {
        $this->displayColumnSelector = $displayColumnSelector;

        return $this;
    }

    public function isRowSelectorVisible(): bool
    {
        return $this->displayRowSelector;
    }

    public function isColumnSelectorVisible(): bool
    {
        return $this->displayColumnSelector;
    }

    public function isToolbarVisible(): bool
    {
        if (! $this->displayToolbar) {
            return false;
        }

        return $this->displayRowSelector || $this->displayColumnSelector;
    }
}
