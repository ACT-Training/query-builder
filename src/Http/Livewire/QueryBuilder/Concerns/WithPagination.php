<?php

namespace ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Concerns;

trait WithPagination
{
    use \Livewire\WithPagination;

    public int $perPage = 10;

    private bool $paginate = true;

    public function usePagination($usePagination = true): static
    {
        $this->paginate = $usePagination;

        return $this;
    }

    public function isPaginated(): bool
    {
        return $this->paginate;
    }

    public function applyPagination($query)
    {
        if ($this->paginate) {
            return $query->paginate($this->perPage);
        } else {
            return $query->get();
        }
    }
}
