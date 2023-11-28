<?php

namespace ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Concerns;

trait WithPagination
{
    use \Livewire\WithPagination;

    public int $perPage = 10;

    public bool $paginate = true;

    public function usePagination($usePagination = true): void
    {
        $this->paginate = $usePagination;
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
