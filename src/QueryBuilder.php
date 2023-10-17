<?php

/** @noinspection PhpUndefinedMethodInspection */

namespace ACTTraining\QueryBuilder;

use ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Collection\CriteriaCollection;
use ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Concerns\WithCaching;
use ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Concerns\WithColumns;
use ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Concerns\WithPagination;
use ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Concerns\WithQueryBuilder;
use ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Concerns\WithRowClick;
use ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Concerns\WithSearch;
use ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Concerns\WithSelecting;
use ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Concerns\WithToolbar;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

abstract class QueryBuilder extends Component
{
    use WithCaching;
    use WithColumns;
    use WithPagination;
    use WithQueryBuilder;
    use WithRowClick;
    use WithSearch;
    use WithSelecting;
    use WithToolbar;

    public string $sortBy = '';

    public string $sortDirection = 'asc';

    public bool $enableQueryBuilder = true;

    public array $displayColumns = [];

    public array $rowOptions = [10, 25, 50];

    protected $listeners = [
        'refreshTable' => '$refresh',
    ];

    //    protected $queryString = ['perPage', 'sortBy', 'sortDirection', 'searchBy'];

    abstract public function query(): Builder;

    public function booted(): void
    {
        $this->config();

        $this->displayColumns = $this->resolveColumns()->pluck('key')->toArray();
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

    public function getRowsQueryProperty()
    {
        /* @phpstan-ignore-next-line */
        $query = $this
            ->query()
            ->apply($this->getCriteriaCollection($this->getCriteriaArray()));

        if ($this->searchBy && $this->searchBy !== '') {
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

        return $query;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getRowsProperty()
    {
        return $this->cache(function () {
            return $this->applyPagination($this->rowsQuery);
        });
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
        if ($this->selectAll) {
            $this->selectedRows = $this->rows->pluck('id')->toArray();
        }

        return view('query-builder::report');
    }
}
