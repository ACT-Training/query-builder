<?php

namespace ACTTraining\QueryBuilder\Support\Concerns;

trait WithPagination
{
    use \Livewire\WithPagination;

    public int $perPage = 10;

    public ?string $pageName = null;

    private bool $paginate = true;

    private bool $scrollToTop = false;

    public function usePagination($usePagination = true): static
    {
        $this->paginate = $usePagination;

        return $this;
    }

    public function scrollToTop($scrollToTop = true): static
    {
        $this->scrollToTop = $scrollToTop;

        return $this;
    }

    public function shouldScroll(): bool
    {
        return $this->scrollToTop;
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
