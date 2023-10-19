<?php

/** @noinspection PhpUndefinedMethodInspection */

namespace ACTTraining\QueryBuilder;

use ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Collection\CriteriaCollection;
use ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Concerns\WithActions;
use ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Concerns\WithCaching;
use ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Concerns\WithColumns;
use ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Concerns\WithPagination;
use ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Concerns\WithQueryBuilder;
use ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Concerns\WithRowClick;
use ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Concerns\WithSearch;
use ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Concerns\WithSelecting;
use ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Concerns\WithSorting;
use ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Concerns\WithToolbar;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

abstract class Table extends Component
{
    use WithColumns;
    use WithPagination;
    use WithRowClick;
    use WithSorting;

    protected string $model = Model::class;

    protected $listeners = [
        'refreshTable' => '$refresh',
    ];

    protected $queryString = ['perPage'];

    abstract public function query(): Builder;

    public function booted(): void
    {
        $this->config();

    }

    public function config(): void
    {
        //
    }


    public function getRowsQueryProperty()
    {
       return $this->query()->when($this->sortBy !== '', function ($query) {
            $query->orderBy($this->sortBy, $this->sortDirection);
        });
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getRowsProperty()
    {
        return $this->applyPagination($this->rowsQuery);
    }

    protected function model(): Model
    {
        $model = $this->model;

        return app($model);
    }

    public function render(): Factory|View
    {
        return view('query-builder::table');
    }
}
