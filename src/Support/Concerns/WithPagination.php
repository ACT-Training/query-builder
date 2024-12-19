<?php

namespace ACTTraining\QueryBuilder\Support\Concerns;

trait WithPagination
{
    use \Livewire\WithPagination;

    public int $perPage = 10;

    public ?string $pageName = null;

    private bool $paginate = true;

    private bool|string $scrollTo = false; //false disables scroll on pagination

    public function usePagination($usePagination = true): static
    {
        $this->paginate = $usePagination;

        return $this;
    }

    public function shouldScrollTo($scrollTo): static
    {
        $this->scrollTo = match ($scrollTo) {
            'top' => true,
            'none' => false,
            default => $scrollTo,
        };

        return $this;
    }

    public function scroll(): bool|string
    {
        return $this->scrollTo;
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
