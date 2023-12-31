<?php

namespace ACTTraining\QueryBuilder\Support\Concerns;

trait WithSearch
{
    protected $searchable = false;

    protected $displaySearch = true;

    protected $displaySearchableIcon = true;

    public $searchBy = '';

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

    public function isSearchVisible(): bool
    {
        return $this->displaySearch;
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

        return $searchableKeys->toArray();
    }

    public function updatedSearchBy(): void
    {
        $this->resetPage();
    }
}
