<?php

/** @noinspection PhpMultipleClassDeclarationsInspection */

/** @noinspection PhpUndefinedMethodInspection */

namespace ACTTraining\QueryBuilder;

use ACTTraining\QueryBuilder\Support\Collection\CriteriaCollection;
use ACTTraining\QueryBuilder\Support\Concerns\WithActions;
use ACTTraining\QueryBuilder\Support\Concerns\WithCaching;
use ACTTraining\QueryBuilder\Support\Concerns\WithColumns;
use ACTTraining\QueryBuilder\Support\Concerns\WithFilters;
use ACTTraining\QueryBuilder\Support\Concerns\WithIdentifier;
use ACTTraining\QueryBuilder\Support\Concerns\WithIndicator;
use ACTTraining\QueryBuilder\Support\Concerns\WithPagination;
use ACTTraining\QueryBuilder\Support\Concerns\WithQueryBuilder;
use ACTTraining\QueryBuilder\Support\Concerns\WithRowClick;
use ACTTraining\QueryBuilder\Support\Concerns\WithRowInjection;
use ACTTraining\QueryBuilder\Support\Concerns\WithSearch;
use ACTTraining\QueryBuilder\Support\Concerns\WithSelecting;
use ACTTraining\QueryBuilder\Support\Concerns\WithSorting;
use ACTTraining\QueryBuilder\Support\Concerns\WithToolbar;
use ACTTraining\QueryBuilder\Support\Concerns\WithViews;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

abstract class QueryBuilder extends Component
{

    use WithActions;
    use WithCaching;
    use WithColumns;
    use WithFilters;
    use WithIdentifier;
    use WithIndicator;
    use WithPagination;
    use WithQueryBuilder;
    use WithRowClick;
    use WithRowInjection;
    use WithSearch;
    use WithSelecting;
    use WithSorting;
    use WithToolbar;
    use WithViews;

    protected string $model = Model::class;

    public bool $enableQueryBuilder = true;

    public array $displayColumns = [];

    public array $rowOptions = [10, 25, 50];

    protected $listeners = [
        'refreshTable' => '$refresh',
    ];

    //    protected array $queryString = ['perPage'];

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
        //        return $this->cache(function () {
        return $this->applyPagination($this->rowsQuery); /* @phpstan-ignore-line */
        //        });
    }

    protected function model(): Model
    {
        $model = $this->model;

        return app($model);
    }

    public function render(): Factory|View
    {
        if ($this->selectAll) {
            $this->selectedRows = $this->rows->pluck('id')->toArray(); /* @phpstan-ignore-line */
        }

        return view('query-builder::query-table');
    }
}
