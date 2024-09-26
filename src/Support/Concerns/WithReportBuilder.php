<?php

namespace ACTTraining\QueryBuilder\Support\Concerns;

use ACTTraining\QueryBuilder\Support\Columns\BooleanColumn;
use ACTTraining\QueryBuilder\Support\Columns\Column;
use ACTTraining\QueryBuilder\Support\Columns\DateColumn;
use ACTTraining\QueryBuilder\Support\Conditions\BooleanCondition;
use ACTTraining\QueryBuilder\Support\Conditions\DateCondition;
use ACTTraining\QueryBuilder\Support\Conditions\TextCondition;

trait WithReportBuilder
{
    public array $selectedColumns = [];

    public function updatedSelectedColumns($value): void
    {
        $this->resetPage();
        ray($value);
    }

    public function availableColumns(): array
    {
        return [];
    }

    public function configuredColumns(): array
    {
        return [];
    }

    public function buildColumns(): array
    {
        $columns = [];

        $counter = 0;

        foreach ($this->configuredColumns() as $column) {
            $columnToAdd = match ($column['type'] ?? null) {
                'boolean' => BooleanColumn::make($column['label'], $column['key'])->justify('center')->hideIf(false),
                'date' => DateColumn::make($column['label'], $column['key'])->format(config('settings.date.short-format'))->justify('right'),
                default => Column::make($column['label'], $column['key'])
            };

            if ($column['view'] ?? false) {
                $columnToAdd->component($column['view']);
            } elseif($counter === 0) {
                $columnToAdd->component('columns.common.title');
            }

            $columns[] = $columnToAdd;

            $counter++;
        }
        return $columns;
    }

    public function buildConditions(): array
    {
        $conditions = [];

        foreach ($this->configuredColumns() as $column) {
            if ($column['skipCondition'] ?? false) {
                continue;
            }
            $conditions[] = match ($column['type'] ?? null) {
                'boolean' => BooleanCondition::make($column['label'], $column['key']),
                'date' => DateCondition::make($column['label'], $column['key']),
                default => TextCondition::make($column['label'], $column['key'])
            };

        }
        return $conditions;
    }

}
