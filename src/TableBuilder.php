<?php

/** @noinspection PhpUndefinedMethodInspection */

namespace ACTTraining\QueryBuilder;

use ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Concerns\WithColumns;
use ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Concerns\WithFilters;
use ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Concerns\WithIndicator;
use ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Concerns\WithMessage;
use ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Concerns\WithPagination;
use ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Concerns\WithRowClick;
use ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Concerns\WithRowInjection;
use ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Concerns\WithSorting;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Livewire\Component;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

abstract class TableBuilder extends Component
{
    use WithColumns;
    use WithFilters;
    use WithIndicator;
    use WithMessage;
    use WithPagination;
    use WithRowClick;
    use WithRowInjection;
    use WithSorting;

    protected string $model = Model::class;

    protected $listeners = [
        'refreshTable' => '$refresh',
    ];

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
        $query = $this->query()->when($this->sortBy !== '', function ($query) {
            $query->orderBy($this->sortBy, $this->sortDirection);
        });

        $dottedFilterValue = Arr::dot($this->filterValues);

        foreach ($this->filters() as $filter) {
            $value = $dottedFilterValue[$filter->code()] ?? null;

            $query->when($value !== null, function ($query) use ($filter, $value) {
                $value = $filter->parseValue($value);
                if (Str::contains($filter->key(), '.')) {
                    $relation = Str::beforeLast($filter->key(), '.');
                    $column = Str::afterLast($filter->key(), '.');

                    $query->whereHas($relation, function ($query) use ($filter, $value, $column) {
                        $query->where($column, $filter->operator(), $value);
                    });
                } else {
                    $query->where($filter->key(), $filter->operator(), $value);
                }
            });
        }

        return $query;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getRowsProperty()
    {
        return $this->applyPagination($this->rowsQuery); /* @phpstan-ignore-line */
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

    /**
     * The view to add markup above the table.
     */
    public function headerView(): string
    {
        return 'query-builder::header';
    }

    /**
     * The view to add markup below the table.
     */
    public function footerView(): string
    {
        return 'query-builder::footer';
    }
}
