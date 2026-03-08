<?php

namespace ACTTraining\QueryBuilder\Support\Concerns;

use ACTTraining\QueryBuilder\Support\Columns\BooleanColumn;
use ACTTraining\QueryBuilder\Support\Columns\Column;
use ACTTraining\QueryBuilder\Support\Columns\DateColumn;
use ACTTraining\QueryBuilder\Support\Columns\ViewColumn;
use ACTTraining\QueryBuilder\Support\Conditions\BooleanCondition;
use ACTTraining\QueryBuilder\Support\Conditions\DateCondition;
use ACTTraining\QueryBuilder\Support\Conditions\EnumCondition;
use ACTTraining\QueryBuilder\Support\Conditions\FloatCondition;
use ACTTraining\QueryBuilder\Support\Conditions\NullCondition;
use ACTTraining\QueryBuilder\Support\Conditions\NumberCondition;
use ACTTraining\QueryBuilder\Support\Conditions\TextCondition;
use Illuminate\Http\Response;
use Livewire\Attributes\Validate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

trait WithReportBuilder
{
    #[Validate('required|array')]
    public array $selectedColumns = [];

    public ?string $groupBy = null;

    public string $aggregateColumn = 'id';

    public string $aggregateFunction = 'COUNT';

    public bool $enableGroupBy = false;

    private function findElementByKey(array $array, $targetValue): ?array
    {
        foreach ($array as $value) {
            if (is_array($value)) {
                if (isset($value['key']) && $value['key'] === $targetValue) {
                    return $value;
                }

                $result = $this->findElementByKey($value, $targetValue);
                if ($result !== null) {
                    return $result;
                }
            }
        }

        return null;
    }

    public function updatedSelectedColumns(): void
    {
        $this->resetPage();
        $this->displayColumns = $this->resolveColumns()->pluck('key')->toArray();
    }

    public function updatedGroupBy(): void
    {
        $this->resetPage();
        $this->displayColumns = $this->resolveColumns()->pluck('key')->toArray();
    }

    public function updatedAggregateFunction(): void
    {
        $this->resetPage();
        $this->displayColumns = $this->resolveColumns()->pluck('key')->toArray();
    }

    public function updatedAggregateColumn(): void
    {
        $this->resetPage();
        $this->displayColumns = $this->resolveColumns()->pluck('key')->toArray();
    }

    public function hasGroupBy(): bool
    {
        return $this->enableGroupBy && $this->groupBy !== null;
    }

    public function aggregateFunctions(): array
    {
        return ['COUNT', 'SUM', 'AVG', 'MIN', 'MAX'];
    }

    public function availableGroupByColumns(): array
    {
        return $this->availableColumns();
    }

    public function groupableColumns(): array
    {
        $columns = [];

        foreach ($this->availableGroupByColumns() as $section => $sectionColumns) {
            foreach ($sectionColumns as $column) {
                if (! is_array($column) || ! isset($column['label'], $column['key'])) {
                    continue;
                }

                $columns[] = $column;
            }
        }

        return $columns;
    }

    public function aggregatableColumns(): array
    {
        $columns = [['label' => 'ID', 'key' => 'id']];

        foreach ($this->availableColumns() as $section => $sectionColumns) {
            foreach ($sectionColumns as $column) {
                if (! is_array($column) || ! isset($column['label'], $column['key'])) {
                    continue;
                }

                $type = $column['type'] ?? null;
                if (in_array($type, ['number', 'float'], true)) {
                    $columns[] = $column;
                }
            }
        }

        return $columns;
    }

    public function availableColumns(): array
    {
        return [];
    }

    public function configuredColumns(): array
    {
        $columns = [];

        foreach ($this->selectedColumns as $column) {
            $columns[] = $this->findElementByKey($this->availableColumns(), $column);
        }

        return $columns;
    }

    public function buildColumns(): array
    {
        $columns = [];
        $counter = 0;

        foreach (($this->configuredColumns() ?? []) as $column) {
            if (! is_array($column) || ! isset($column['label'], $column['key'])) {
                continue;
            }

            $type = $column['type'] ?? null;

            $columnToAdd = match ($type) {
                'number' => Column::make($column['label'], $column['key'])->justify('right'),
                'boolean' => BooleanColumn::make($column['label'], $column['key'])->justify('center')->hideIf(false),
                'date' => DateColumn::make($column['label'], $column['key'])
                    ->format(config('settings.date.short-format'))
                    ->justify('right'),
                'view' => ViewColumn::make($column['label']),
                default => Column::make($column['label'], $column['key']),
            };

            if (! empty($column['view'])) {
                $columnToAdd->component($column['view']);
            } elseif ($counter === 0) {
                $columnToAdd->component('columns.common.title');
            }

            if (! empty($column['sortable'])) {
                $columnToAdd->sortable();
            }

            if (! empty($column['justify'])) {
                $columnToAdd->justify($column['justify']);
            }

            $columns[] = $columnToAdd;
            $counter++;
        }

        if ($this->hasGroupBy()) {
            $groupByLabel = $this->findElementByKey($this->availableColumns(), $this->groupBy)['label'] ?? $this->groupBy;

            return [
                Column::make($groupByLabel, 'group_value')->sortable(),
                Column::make("{$this->aggregateFunction}({$this->aggregateColumn})", 'aggregate')
                    ->justify('right')
                    ->sortable(),
            ];
        }

        return $columns;
    }

    public function buildConditions(): array
    {
        $conditions = [];

        foreach (($this->configuredColumns() ?? []) as $column) {
            if (! is_array($column) || ! isset($column['label'], $column['key'])) {
                continue;
            }

            if (! empty($column['skipCondition'])) {
                continue;
            }

            $type = $column['type'] ?? null;

            $options = $column['options'] ?? [];
            if (! is_array($options)) {
                $options = [];
            }

            $conditions[] = match ($type) {
                'number' => NumberCondition::make($column['label'], $column['key']),
                'enum' => EnumCondition::make($column['label'], $column['key'], $options),
                'float' => FloatCondition::make($column['label'], $column['key']),
                'boolean' => BooleanCondition::make($column['label'], $column['key']),
                'date' => DateCondition::make($column['label'], $column['key']),
                'null' => NullCondition::make($column['label'], $column['key']),
                default => TextCondition::make($column['label'], $column['key']),
            };
        }

        return $conditions;
    }

    public function removeCriteria($index): void
    {
        unset($this->criteria[$index]);
        $this->criteria = array_values($this->criteria);

        $this->saveToSession();
    }

    public function resetReportBuilder(): void
    {
        $this->criteria = [];
        $this->selectedColumns = [];
        $this->groupBy = null;
        $this->aggregateColumn = 'id';
        $this->aggregateFunction = 'COUNT';
        $this->saveToSession();
    }

    public function exportReportBuilder(): Response|BinaryFileResponse|null
    {
        return null;
    }

    public function saveReportBuilder(): void
    {
        //
    }

    public function loadReportBuilder(): void
    {
        //
    }

    public function saveToSession(): void
    {
        //
    }
}
