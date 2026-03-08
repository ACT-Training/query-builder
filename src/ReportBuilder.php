<?php

/** @noinspection PhpUndefinedMethodInspection */

namespace ACTTraining\QueryBuilder;

use ACTTraining\QueryBuilder\Support\Concerns\WithReportBuilder;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Livewire\Attributes\Computed;

abstract class ReportBuilder extends QueryBuilder
{
    use WithReportBuilder;

    public bool $selectable = false;

    #[Computed]
    public function rowsQuery()
    {
        $query = parent::rowsQuery();

        if (! $this->hasGroupBy()) {
            return $query;
        }

        if (! in_array($this->aggregateFunction, $this->aggregateFunctions(), true)) {
            return $query;
        }

        $groupByColumn = $this->resolveColumnWithJoins($query, $this->groupBy);
        $baseTable = $query->getModel()->getTable();
        $aggregateColumn = str_contains($this->aggregateColumn, '.')
            ? $this->resolveColumnWithJoins($query, $this->aggregateColumn)
            : "{$baseTable}.{$this->aggregateColumn}";

        $query->withoutGlobalScope('order')
            ->reorder()
            ->selectRaw("{$groupByColumn} as group_value, {$this->aggregateFunction}({$aggregateColumn}) as aggregate")
            ->groupBy($groupByColumn)
            ->orderBy($groupByColumn);

        return $query;
    }

    protected function resolveColumnWithJoins(Builder $query, string $key): string
    {
        if (! str_contains($key, '.')) {
            return $query->getModel()->getTable().'.'.$key;
        }

        $parts = explode('.', $key);
        $columnName = array_pop($parts);
        $currentModel = $query->getModel();
        $joined = [];

        foreach ($parts as $relationName) {
            if (! method_exists($currentModel, $relationName)) {
                return $key;
            }

            $relation = $currentModel->{$relationName}();
            $relatedTable = $relation->getRelated()->getTable();

            if (in_array($relatedTable, $joined, true)) {
                $currentModel = $relation->getRelated();

                continue;
            }

            if ($relation instanceof BelongsTo) {
                $query->join(
                    $relatedTable,
                    $currentModel->getTable().'.'.$relation->getForeignKeyName(),
                    '=',
                    $relatedTable.'.'.$relation->getOwnerKeyName()
                );
            } elseif ($relation instanceof HasOne || $relation instanceof HasMany) {
                $query->join(
                    $relatedTable,
                    $currentModel->getTable().'.'.$currentModel->getKeyName(),
                    '=',
                    $relatedTable.'.'.$relation->getForeignKeyName()
                );
            }

            $joined[] = $relatedTable;
            $currentModel = $relation->getRelated();
        }

        return $currentModel->getTable().'.'.$columnName;
    }

    public function render(): Factory|View
    {
        return view('query-builder::report-table');
    }
}
