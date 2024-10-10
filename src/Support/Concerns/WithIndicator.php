<?php

namespace ACTTraining\QueryBuilder\Support\Concerns;

trait WithIndicator
{
    protected bool $useIndicator = false;

    protected bool $showOverlay = true;

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

    public function showOverlay(): bool
    {
        return $this->showOverlay;
    }

    public function loadingIndicator(bool $useIndicator = true, $loadingClass = 'opacity-50', $showOverlay = true): static
    {
        $this->useIndicator = $useIndicator;
        $this->loadingClass = $loadingClass;
        $this->showOverlay = $showOverlay;

        return $this;
    }
}
