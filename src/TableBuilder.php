<?php

/** @noinspection PhpUndefinedMethodInspection */

namespace ACTTraining\QueryBuilder;

use ACTTraining\QueryBuilder\Support\Concerns\WithActions;
use ACTTraining\QueryBuilder\Support\Concerns\WithColumns;
use ACTTraining\QueryBuilder\Support\Concerns\WithFilters;
use ACTTraining\QueryBuilder\Support\Concerns\WithIdentifier;
use ACTTraining\QueryBuilder\Support\Concerns\WithIndicator;
use ACTTraining\QueryBuilder\Support\Concerns\WithMessage;
use ACTTraining\QueryBuilder\Support\Concerns\WithPagination;
use ACTTraining\QueryBuilder\Support\Concerns\WithRowClick;
use ACTTraining\QueryBuilder\Support\Concerns\WithRowInjection;
use ACTTraining\QueryBuilder\Support\Concerns\WithSearch;
use ACTTraining\QueryBuilder\Support\Concerns\WithSelecting;
use ACTTraining\QueryBuilder\Support\Concerns\WithSorting;
use ACTTraining\QueryBuilder\Support\Concerns\WithViews;
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
    use WithActions;
    use WithColumns;
    use WithFilters;
    use WithIdentifier;
    use WithIndicator;
    use WithMessage;
    use WithPagination;
    use WithRowClick;
    use WithRowInjection;
    use WithSearch;
    use WithSelecting;
    use WithSorting;
    use WithViews;

    protected string $model = Model::class;

    public array $rowOptions = [10, 25, 50];

    protected $listeners = [
        'refreshTable' => '$refresh',
    ];

    abstract public function query(): Builder;

    protected function queryString(): array
    {
        return [
            'searchBy' => [
                'as' => 's',
            ],
            'filterValues' => [
                'as' => 'f',
            ],
        ];
    }

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

        if ($this->searchBy && $this->searchBy !== '') {
            foreach ($this->getSearchableColumns() as $column) {
                // Explode the column to separate relationships and the actual column name
                $parts = explode('.', $column);

                // Extract the actual column name from the end of the array
                $actualColumnName = array_pop($parts);

                // If there are relationships defined (implied by remaining elements in $parts)
                if (! empty($parts)) {
                    // Build the relationship string from the remaining $parts
                    $relationshipPath = implode('.', $parts);

                    // Use a closure to apply the search condition on the related model
                    $query->orWhereHas($relationshipPath, function ($query) use ($actualColumnName) {
                        $query->where($actualColumnName, 'like', '%'.$this->searchBy.'%');
                    });
                } else {
                    // If there are no relationships, directly apply the search condition on the current model
                    $query->orWhere($actualColumnName, 'like', '%'.$this->searchBy.'%');
                }
            }
        }

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
}
