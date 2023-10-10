<?php

namespace ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Concerns;

use ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Collection\AllOfCriteriaCollection;
use ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Collection\CriteriaCollection;
use ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Collection\OneOfCriteriaCollection;
use ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Criteria\BooleanCriteria;
use ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Criteria\CompareCriteria;
use ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Criteria\DateCriteria;
use ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Criteria\LikeCriteria;
use ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Criteria\NullCriteria;
use Illuminate\Support\Arr;
use Illuminate\Support\Enumerable;

trait WithQueryBuilder
{
    public array $criteria = [];

    public string $andOr = 'and';

    public function addCriteria(): void
    {
        $this->resetPage();

        $conditions = $this->resolveConditions();
        $condition = Arr::first($conditions);

        $operations = $condition->toArray()['operations'];
        $firstOperationKey = array_key_first($operations);

        $this->criteria[] = [
            'column' => $condition->key,
            'operation' => $firstOperationKey,
            'value' => null,
            'extraValue' => null,
            'requiresExtra' => false,
            'displayValue' => $condition->displayValue(),
            'inputType' => $condition->inputType(),
        ];
    }

    public function conditions(): array
    {
        return [];
    }

    public function updatedCriteria($value, $indexOrKeyName): void
    {
        $this->resetPage();

        [$index, $keyName] = explode('.', $indexOrKeyName);

        if ($keyName === 'column') {
            $operations = $this->operations($value);
            $firstOperationKey = array_key_first($operations);

            $this->criteria[$index] = [
                'column' => $value,
                'operation' => $firstOperationKey,
                'value' => null,
                'extraValue' => null,
                'requiresExtra' => false,
                'displayValue' => $this->displayValueForOperation($firstOperationKey),
                'inputType' => $this->inputTypeForCondition($value),
            ];
        }

        if ($keyName === 'operation') {
            $this->criteria[$index]['displayValue'] = $this->displayValueForOperation($value);
            if (! $this->criteria[$index]['displayValue']) {
                $this->criteria[$index]['value'] = null;
                $this->criteria[$index]['extraValue'] = null;
            }
            $this->criteria[$index]['requiresExtra'] = $this->displayExtraValueForOperation($value);
        }
    }

    public function operations(string $key): array
    {
        $conditions = $this->resolveConditions();
        $condition = $conditions->firstWhere('key', $key);

        return $condition->toArray()['operations'];
    }

    public function displayValueForOperation($value): bool
    {
        return ! in_array($value, ['is_set', 'is_not_set', 'is_true', 'is_false', 'is_null', 'is_not_null']);
    }

    public function inputTypeForCondition($key): string
    {
        $conditions = $this->resolveConditions();
        $condition = $conditions->firstWhere('key', $key);

        return $condition->inputType();
    }

    public function displayExtraValueForOperation($value): bool
    {
        return in_array($value, ['is_between', 'is_not_between']);
    }

    public function removeCriteria($index): void
    {
        unset($this->criteria[$index]);
        $this->criteria = array_values($this->criteria);
    }

    public function setAndOr($condition): void
    {
        $this->andOr = $condition;
    }

    public function resetQuery(): void
    {
        $this->resetPage();
        $this->criteria = [];
    }

    protected function resolveConditions(): Enumerable
    {
        return collect($this->conditions());
    }

    protected function getCriteriaCollection($criteriaArray): OneOfCriteriaCollection|AllOfCriteriaCollection
    {
        return match ($this->andOr) {
            'and' => CriteriaCollection::allOf($criteriaArray),
            'or' => CriteriaCollection::oneOf($criteriaArray),
        };
    }

    protected function getCriteriaArray(): array
    {
        $classArray = [];

        foreach ($this->criteria as $criterion) {
            $class = $this->getCriteriaClass($criterion);
            if ($class !== null) {
                $classArray[] = $class;
            }
        }

        return $classArray;
    }

    private function getCriteriaClass($criteria): CompareCriteria|LikeCriteria|BooleanCriteria|DateCriteria|NullCriteria|null
    {
        $column = $criteria['column'];
        $value = $criteria['value'] ?? null;
        $extraValue = $criteria['extraValue'] ?? null;

        if (is_null($value) && $criteria['displayValue']) {
            return null;
        }

        return match ($criteria['operation']) {
            'not_equals' => new CompareCriteria($column, $value, '!='),
            'contains' => new LikeCriteria($column, '%'.$value.'%'),
            'not_contains' => new LikeCriteria($column, '%'.$value.'%', 'not like'),
            'starts_with' => new LikeCriteria($column, $value.'%'),
            'ends_with' => new LikeCriteria($column, '%'.$value),
            'is_true' => new BooleanCriteria($column, true),
            'is_false' => new BooleanCriteria($column, false),
            'is' => new DateCriteria($column, $value, '='),
            'is_not' => new DateCriteria($column, $value, '!='),
            'is_before' => new DateCriteria($column, $value, '<'),
            'is_on_or_before' => new DateCriteria($column, $value, '<='),
            'is_after' => new DateCriteria($column, $value, '>'),
            'is_on_or_after' => new DateCriteria($column, $value, '>='),
            'is_between' => new DateCriteria($column, [$value, $extraValue], 'between'),
            'is_not_between' => new DateCriteria($column, [$value, $extraValue], 'not between'),
            'is_set' => new NullCriteria($column, 'is_set'),
            'is_not_set' => new NullCriteria($column, 'is_not_set'),
            default => new CompareCriteria($column, $value),
        };
    }
}
