<?php

/** @noinspection PhpMultipleClassDeclarationsInspection */

/** @noinspection PhpUndefinedMethodInspection */

namespace ACTTraining\QueryBuilder;

use ACTTraining\QueryBuilder\Support\Collection\CriteriaCollection;
use ACTTraining\QueryBuilder\Support\Columns\BaseColumn;
use ACTTraining\QueryBuilder\Support\Concerns\WithActions;
use ACTTraining\QueryBuilder\Support\Concerns\WithCaching;
use ACTTraining\QueryBuilder\Support\Concerns\WithCardView;
use ACTTraining\QueryBuilder\Support\Concerns\WithColumns;
use ACTTraining\QueryBuilder\Support\Concerns\WithFilters;
use ACTTraining\QueryBuilder\Support\Concerns\WithFooterRow;
use ACTTraining\QueryBuilder\Support\Concerns\WithIdentifier;
use ACTTraining\QueryBuilder\Support\Concerns\WithIndicator;
use ACTTraining\QueryBuilder\Support\Concerns\WithPagination;
use ACTTraining\QueryBuilder\Support\Concerns\WithQueryBuilder;
use ACTTraining\QueryBuilder\Support\Concerns\WithRowClick;
use ACTTraining\QueryBuilder\Support\Concerns\WithRowInjection;
use ACTTraining\QueryBuilder\Support\Concerns\WithSavedViews;
use ACTTraining\QueryBuilder\Support\Concerns\WithSearch;
use ACTTraining\QueryBuilder\Support\Concerns\WithSelecting;
use ACTTraining\QueryBuilder\Support\Concerns\WithSorting;
use ACTTraining\QueryBuilder\Support\Concerns\WithToolbar;
use ACTTraining\QueryBuilder\Support\Concerns\WithViews;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * @method saveToSession()
 */
abstract class QueryBuilder extends Component
{
    use WithActions;
    use WithCaching;
    use WithCardView;
    use WithColumns;
    use WithFilters;
    use WithFooterRow;
    use WithIdentifier;
    use WithIndicator;
    use WithPagination;
    use WithQueryBuilder;
    use WithRowClick;
    use WithRowInjection;
    use WithSavedViews;
    use WithSearch;
    use WithSelecting;
    use WithSorting;
    use WithToolbar;
    use WithViews;

    protected string $model = Model::class;

    public bool $enableQueryBuilder = true;

    public array $displayColumns = [];

    public array $rowOptions = [10, 25, 50];

    abstract public function query(): Builder;

    #[On('refreshTable')]
    public function refreshTable(): void {}

    public function booted(): void
    {
        $this->config();

        // Without saved views every request shows every column again, as it always
        // has. With them, the columns someone chose (or a view applied) must survive
        // the next request, so only the first request fills them in.
        if (! $this->hasSavedViews() || ! $this->savedViewsBooted) {
            $this->displayColumns = $this->resolveColumns()->pluck('key')->toArray();
        }
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

    public function showQueryBuilder(): bool
    {
        return $this->enableQueryBuilder && count($this->conditions());
    }

    protected function isColumnDisplayed(BaseColumn $column): bool
    {
        return in_array($column->key, $this->displayColumns, true);
    }

    protected function savedViewFilterState(): array
    {
        $criteria = array_map(fn (array $criterion) => [
            'column' => $criterion['column'],
            'operation' => $criterion['operation'],
            'value' => $criterion['value'] ?? null,
            'extraValue' => $criterion['extraValue'] ?? null,
        ], $this->criteria);

        return [
            'criteria' => $criteria,
            'andOr' => $this->andOr,
            'columns' => $this->displayColumns,
        ];
    }

    protected function applySavedViewFilterState(array $state): void
    {
        $conditions = $this->resolveConditions();
        $criteria = [];

        foreach ($state['criteria'] ?? [] as $saved) {
            $condition = $conditions->firstWhere('key', $saved['column'] ?? null);
            $operation = $saved['operation'] ?? null;

            if (! $condition || ! is_string($operation) || ! array_key_exists($operation, $condition->operations)) {
                continue;
            }

            $criteria[] = [
                'column' => $condition->key,
                'operation' => $operation,
                'value' => $saved['value'] ?? null,
                'extraValue' => $saved['extraValue'] ?? null,
                'requiresExtra' => $this->displayExtraValueForOperation($operation),
                'displayValue' => $this->displayValueForOperation($operation),
                'inputType' => $condition->inputType,
                'factor' => $condition->factor,
                'options' => $condition->options ?? [],
            ];
        }

        $this->criteria = $criteria;
        $this->andOr = ($state['andOr'] ?? 'and') === 'or' ? 'or' : 'and';

        $allColumns = $this->resolveColumns()->pluck('key')->all();
        $columns = array_values(array_intersect($allColumns, (array) ($state['columns'] ?? [])));

        $this->displayColumns = $columns ?: $allColumns;
    }

    #[Computed]
    public function rowsQuery()
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

        $query->when($this->sortBy !== '', function ($query) {
            if (Str::contains($this->sortBy, '.')) {
                // If it's a relationship, use orderByRelated
                $this->orderByRelated($query, $this->sortBy, $this->sortDirection);
            } else {
                // Otherwise, use regular orderBy
                $query->orderBy($this->sortBy, $this->sortDirection);
            }
        });

        return $query;
    }

    #[Computed]
    public function rows()
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
            $this->selectedRows = $this->pageRowKeys();
        }

        return view('query-builder::query-table');
    }
}
