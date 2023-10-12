<?php

/** @noinspection PhpUndefinedMethodInspection */

namespace ACTTraining\QueryBuilder;

use ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Collection\CriteriaCollection;
use ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Concerns\WithColumns;
use ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Concerns\WithQueryBuilder;
use ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Concerns\WithRowClick;
use ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Concerns\WithSearch;
use ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Concerns\WithToolbar;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;
use Livewire\WithPagination;

abstract class QueryBuilder extends Component
{
    use WithColumns;
    use WithPagination;
    use WithQueryBuilder;
    use WithRowClick;
    use WithSearch;
    use WithToolbar;

    public int $perPage = 10;

    public string $sortBy = '';

    public string $sortDirection = 'asc';

    public bool $paginate = true;

    public bool $enableQueryBuilder = true;

    public array $displayColumns = [];

    public array $rowOptions = [10, 25, 50];

    protected $listeners = [
        'refreshTable' => '$refresh',
    ];

    abstract public function query(): Builder;

    public function booted(): void
    {
        $this->config();

        $this->displayColumns = $this->allColumns();
    }

    public function config(): void
    {
        $this
            ->displaySearch()
            ->displaySearchableIcon()
            ->displayToolbar()
            ->displayRowSelector()
            ->displayColumnSelector();
    }

    public function enableQueryBuilder(bool $enableQueryBuilder = true): static
    {
        $this->enableQueryBuilder = $enableQueryBuilder;

        return $this;
    }

    public function data(): Collection|LengthAwarePaginator|array|\Illuminate\Pagination\LengthAwarePaginator
    {
        /* @phpstan-ignore-next-line */
        $query = $this
            ->query()
            ->apply($this->getCriteriaCollection($this->getCriteriaArray()));

        if ($this->searchBy !== '') {
            $criteria = [];
            foreach ($this->getSearchableColumns() as $column) {
                $criteria[] = [
                    'column' => $column,
                    'operation' => 'contains',
                    'value' => $this->searchBy,
                    'displayValue' => true,
                ];
            }
            $query->apply(CriteriaCollection::oneOf($this->getCriteriaArray($criteria)));
        }

        $query->when($this->sortBy !== '', function ($query) {
            $query->orderBy($this->sortBy, $this->sortDirection);
        });

        if ($this->paginate) {
            return $query->paginate($this->perPage);
        }

        return $query->get();
    }

    public function sort($key): void
    {
        $this->resetPage();

        if ($this->sortBy === $key) {
            $direction = $this->sortDirection === 'asc' ? 'desc' : 'asc';
            $this->sortDirection = $direction;

            return;
        }

        $this->sortBy = $key;
        $this->sortDirection = 'asc';
    }

    public function render(): Factory|View
    {
        return view('query-builder::report');
    }
}
