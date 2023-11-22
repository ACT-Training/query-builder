<?php

namespace ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Concerns;

trait WithIndicator
{
    protected $useIndicator = false;

    protected $loadingClass = 'opacity-50';

    public function useLoadingIndicator(): bool
    {
        return $this->useIndicator;
    }

    public function loadingClass(): string
    {
        return $this->loadingClass;
    }

    public function loadingIndicator(bool $useIndicator = true, $loadingClass = 'opacity-50'): static
    {
        $this->useIndicator = $useIndicator;
        $this->loadingClass = $loadingClass;

        return $this;
    }
}
