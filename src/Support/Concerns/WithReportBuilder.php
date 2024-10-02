<?php

namespace ACTTraining\QueryBuilder\Support\Concerns;

use ACTTraining\QueryBuilder\Support\Columns\BooleanColumn;
use ACTTraining\QueryBuilder\Support\Columns\Column;
use ACTTraining\QueryBuilder\Support\Columns\DateColumn;
use ACTTraining\QueryBuilder\Support\Conditions\BooleanCondition;
use ACTTraining\QueryBuilder\Support\Conditions\DateCondition;
use ACTTraining\QueryBuilder\Support\Conditions\EnumCondition;
use ACTTraining\QueryBuilder\Support\Conditions\FloatCondition;
use ACTTraining\QueryBuilder\Support\Conditions\NumberCondition;
use ACTTraining\QueryBuilder\Support\Conditions\TextCondition;
use Illuminate\Http\Response;
use Livewire\Attributes\Validate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

trait WithReportBuilder
{
    #[Validate('required|array')]
    public array $selectedColumns = [];

    private function findElementByKey(array $array, $targetValue): ?array
    {
        foreach ($array as $value) {
            // Check if the current item is an array
            if (is_array($value)) {
                // Check if it contains the 'key' element with the target value
                if (isset($value['key']) && $value['key'] === $targetValue) {
                    return $value; // Return the found item
                }

                // Recursively search the sub-array
                $result = $this->findElementByKey($value, $targetValue);
                if ($result !== null) {
                    return $result;
                }
            }
        }

        return null; // Return null if no match is found
    }

    public function updatedSelectedColumns(): void
    {
        $this->resetPage();
        $this->dispatch('refreshTable')->self();
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

        foreach ($this->configuredColumns() as $column) {
            $columnToAdd = match ($column['type'] ?? null) {
                'number' => Column::make($column['label'], $column['key'])->justify('right'),
                'boolean' => BooleanColumn::make($column['label'], $column['key'])->justify('center')->hideIf(false),
                'date' => DateColumn::make($column['label'], $column['key'])->format(config('settings.date.short-format'))->justify('right'),
                default => Column::make($column['label'], $column['key'])
            };

            if ($column['view'] ?? false) {
                $columnToAdd->component($column['view']);
            } elseif($counter === 0) {
                $columnToAdd->component('columns.common.title');
            }

            if ($column['sortable'] ?? false) {
                $columnToAdd->sortable();
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
                'number' => NumberCondition::make($column['label'], $column['key']),
                'enum' => EnumCondition::make($column['label'], $column['key'], $column['options'] ?? []),
                'float' => FloatCondition::make($column['label'], $column['key']),
                'boolean' => BooleanCondition::make($column['label'], $column['key']),
                'date' => DateCondition::make($column['label'], $column['key']),
                default => TextCondition::make($column['label'], $column['key'])
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
    }

    public function exportReportBuilder(): Response|BinaryFileResponse|null
    {
        return null;
    }

    public function saveReportBuilder(): void
    {
        //
    }

    public function loadReportBuilder($id): void
    {
        //
    }

    public function saveToSession(): void
    {
        //
    }
}
