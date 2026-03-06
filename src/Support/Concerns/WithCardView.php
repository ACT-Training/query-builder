<?php

namespace ACTTraining\QueryBuilder\Support\Concerns;

use ACTTraining\QueryBuilder\Support\CardLayout;

trait WithCardView
{
    public string $viewMode = 'table';

    public function cardLayout(): ?CardLayout
    {
        return null;
    }

    public function toggleViewMode(): void
    {
        $this->viewMode = $this->viewMode === 'table' ? 'cards' : 'table';
    }

    public function isCardViewEnabled(): bool
    {
        return $this->cardLayout() !== null;
    }

    public function isCardMode(): bool
    {
        return $this->isCardViewEnabled() && $this->viewMode === 'cards';
    }
}
