<?php

namespace ACTTraining\QueryBuilder\Support\Concerns;

use Livewire\Attributes\Session;

trait WithSearch
{
    protected bool $searchable = false;

    protected bool $displaySearch = true;

    protected bool $displaySearchableIcon = true;

    public string $searchBy = '';

    protected array $additionalSearchables = [];

    public function isSearchable(): bool
    {
        return $this->searchable;
    }

    public function searchable(bool $searchable = true): static
    {
        $this->searchable = $searchable;

        $this->isSearchable = $searchable;

        return $this;
    }

    public function displaySearch(bool $displaySearch = true): static
    {
        $this->displaySearch = $displaySearch;

        return $this;
    }

    public function additionalSearchables(array $additionalSearchables): static
    {
        $this->additionalSearchables = $additionalSearchables;

        return $this;
    }

    public function isSearchVisible(): bool
    {
        return $this->displaySearch;
    }

    public function isSearchActive(): bool
    {
        return $this->searchBy !== '';
    }

    public function displaySearchableIcon(bool $displaySearchableIcon = true): static
    {
        $this->displaySearchableIcon = $displaySearchableIcon;

        return $this;
    }

    public function isSearchableIconVisible(): bool
    {
        return $this->displaySearchableIcon;
    }

    public function getSearchableColumns(): array
    {
        $searchableKeys = $this->resolveColumns()->filter(function ($class) {
            return $class->isSearchable;
        })->pluck('key');

        return array_merge($searchableKeys->toArray(), $this->additionalSearchables);
    }

    public function updatedSearchBy(): void
    {
        $this->resetPage();
    }

    public function resetSearch(): void
    {
        $this->searchBy = '';
        $this->resetPage();
    }

    public function searchableColumnsSet(): bool
    {
        return count($this->getSearchableColumns()) > 0;
    }
}
