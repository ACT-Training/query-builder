<?php

/** @noinspection PhpUndefinedMethodInspection */

namespace ACTTraining\QueryBuilder;

use ACTTraining\QueryBuilder\Support\Concerns\WithReportBuilder;
use ACTTraining\QueryBuilder\Support\RelationGuard;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\Relation;
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

        [$aggregateFunction, $aggregateColumnKey] = $this->validAggregate();

        $groupByColumn = $this->resolveColumnWithJoins($query, $this->groupBy);
        $baseTable = $query->getModel()->getTable();

        if ($aggregateFunction === 'COUNT') {
            $aggregateColumn = "{$baseTable}.id";
        } else {
            $aggregateColumn = str_contains($aggregateColumnKey, '.')
                ? $this->resolveColumnWithJoins($query, $aggregateColumnKey)
                : "{$baseTable}.{$aggregateColumnKey}";
        }

        $sortColumn = in_array($this->sortBy, ['aggregate', 'group_value'], true)
            ? $this->sortBy
            : $groupByColumn;

        $sortDirection = in_array($this->sortBy, ['aggregate', 'group_value'], true)
            ? $this->sortDirection
            : 'asc';

        $query->withoutGlobalScope('order')
            ->reorder()
            ->selectRaw("{$groupByColumn} as group_value, {$aggregateFunction}({$aggregateColumn}) as aggregate")
            ->groupBy($groupByColumn)
            ->orderBy($sortColumn, $sortDirection);

        return $query;
    }

    /**
     * A grouped report can also sort by its group and its total.
     */
    protected function allowedSortKeys(): array
    {
        $keys = parent::allowedSortKeys();

        if ($this->hasGroupBy()) {
            array_push($keys, 'group_value', 'aggregate');
        }

        return $keys;
    }

    /**
     * Criteria may be on any of the report's columns, also those not displayed.
     */
    protected function allowedCriteriaColumns(): array
    {
        $keys = parent::allowedCriteriaColumns();

        $available = $this->availableColumns();

        array_walk_recursive($available, function ($value, $key) use (&$keys) {
            if ($key === 'key' && is_string($value)) {
                $keys[] = $value;
            }
        });

        return $keys;
    }

    protected function resolveColumnWithJoins(Builder $query, string $key): string
    {
        if (! str_contains($key, '.')) {
            return $query->getModel()->getTable().'.'.$key;
        }

        $parts = explode('.', $key);
        $columnName = array_pop($parts);
        $currentModel = $query->getModel();
        $existingJoins = collect($query->getQuery()->joins ?? [])
            ->pluck('table')
            ->all();

        foreach ($parts as $relationName) {
            if (! RelationGuard::isRelation($currentModel, $relationName)) {
                return $key;
            }

            $relation = $currentModel->{$relationName}();

            if (! $relation instanceof Relation) {
                return $key;
            }
            $relatedTable = $relation->getRelated()->getTable();

            if (in_array($relatedTable, $existingJoins, true)) {
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

            $existingJoins[] = $relatedTable;
            $currentModel = $relation->getRelated();
        }

        return $currentModel->getTable().'.'.$columnName;
    }

    public function render(): Factory|View
    {
        return view('query-builder::report-table');
    }
}
