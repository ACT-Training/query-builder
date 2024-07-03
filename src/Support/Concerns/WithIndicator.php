<?php

namespace ACTTraining\QueryBuilder\Support\Concerns;

trait WithIndicator
{
    protected bool $useIndicator = false;

    protected string $loadingClass = 'opacity-50';

    protected string $spinnerColor = 'text-blue-500';

    public function useLoadingIndicator(): bool
    {
        return $this->useIndicator;
    }

    public function loadingClass(): string
    {
        return $this->loadingClass;
    }

    public function spinnerColor(): string
    {
        return $this->spinnerColor;
    }

    public function setSpinnerColor($color): void
    {
        $this->spinnerColor = $color;
    }

    public function loadingIndicator(bool $useIndicator = true, $loadingClass = 'opacity-50'): static
    {
        $this->useIndicator = $useIndicator;
        $this->loadingClass = $loadingClass;

        return $this;
    }
}
