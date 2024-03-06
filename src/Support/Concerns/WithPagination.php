<?php

namespace ACTTraining\QueryBuilder\Support\Concerns;

trait WithPagination
{
    use \Livewire\WithPagination;

    public int $perPage = 10;

    public ?string $pageName = null;

    private bool $paginate = true;

    public function usePagination($usePagination = true): static
    {
        $this->paginate = $usePagination;

        return $this;
    }

    public function pageName($pageName = null): static
    {
        $this->pageName = $pageName;

        return $this;
    }

    public function isPaginated(): bool
    {
        return $this->paginate;
    }

    public function applyPagination($query)
    {
        if ($this->paginate) {
            if ($this->pageName) {
                return $query->paginate($this->perPage, ['*'], $this->pageName);
            }

            return $query->paginate($this->perPage);
        } else {
            return $query->get();
        }
    }
}
