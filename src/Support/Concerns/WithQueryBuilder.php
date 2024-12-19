<?php

namespace ACTTraining\QueryBuilder\Support\Concerns;

use ACTTraining\QueryBuilder\Support\Collection\AllOfCriteriaCollection;
use ACTTraining\QueryBuilder\Support\Collection\CriteriaCollection;
use ACTTraining\QueryBuilder\Support\Collection\OneOfCriteriaCollection;
use ACTTraining\QueryBuilder\Support\Criteria\BooleanCriteria;
use ACTTraining\QueryBuilder\Support\Criteria\CompareCriteria;
use ACTTraining\QueryBuilder\Support\Criteria\DateCriteria;
use ACTTraining\QueryBuilder\Support\Criteria\LikeCriteria;
use ACTTraining\QueryBuilder\Support\Criteria\NullCriteria;
use Illuminate\Support\Enumerable;

trait WithQueryBuilder
{
    public array $criteria = [];

    public string $andOr = 'and';

    /** @noinspection PhpUndefinedMethodInspection */
    public function addCriteria(): void
    {
        $this->resetPage();

        $conditions = $this->resolveConditions();
        $condition = $conditions->first();

        $operations = $condition->toArray()['operations'];
        $firstOperationKey = array_key_first($operations);

        $this->criteria[] = [
            'column' => $condition->key,
            'operation' => $firstOperationKey,
            'value' => null,
            'extraValue' => null,
            'requiresExtra' => false,
            'displayValue' => $condition->displayValue(),
            'inputType' => $condition->inputType,
            'factor' => $condition->factor,
            'options' => $condition->options ?? [],
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
                'factor' => $this->factorForCondition($value),
                'options' => $this->optionsForCondition($value),
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

        if ($condition) {
            return $condition->toArray()['operations'];
        }

        return [];
    }

    public function displayValueForOperation($value): bool
    {
        return ! in_array($value, ['is_set', 'is_not_set', 'is_true', 'is_false', 'is_null', 'is_not_null']);
    }

    public function inputTypeForCondition($key): string
    {
        $conditions = $this->resolveConditions();
        $condition = $conditions->firstWhere('key', $key);

        return $condition->inputType;
    }

    public function factorForCondition($key): int
    {
        $conditions = $this->resolveConditions();
        $condition = $conditions->firstWhere('key', $key);

        return $condition->factor ?? 1;
    }

    public function optionsForCondition($key): array
    {
        $conditions = $this->resolveConditions();
        $condition = $conditions->firstWhere('key', $key);

        return $condition->options ?? [];
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
            'or' => CriteriaCollection::oneOf($criteriaArray),
            default => CriteriaCollection::allOf($criteriaArray),
        };
    }

    protected function getCriteriaArray($criteria = null): array
    {
        $classArray = [];

        if ($criteria === null) {
            $criteria = $this->criteria;
        }

        foreach ($criteria as $criterion) {
            $class = $this->getCriteriaClass($criterion);
            if ($class !== null) {
                $classArray[] = $class;
            }
        }

        return $classArray;
    }

    private function getCriteriaClass($criteria
    ): CompareCriteria|LikeCriteria|BooleanCriteria|DateCriteria|NullCriteria|null {
        $column = $criteria['column'];
        $value = $criteria['value'] ?? null;
        $extraValue = $criteria['extraValue'] ?? null;
        $factor = $criteria['factor'] ?? null;

        if (is_null($value) && $criteria['displayValue']) {
            return null;
        }

        if ($factor && is_numeric($value) && $factor !== 1) {
            $value = $value * $factor;
        }

        return match ($criteria['operation']) {
            'equals' => new CompareCriteria($column, $value, '='),
            'not_equals' => new CompareCriteria($column, $value, '!='),
            'greater_than' => new CompareCriteria($column, $value, '>'),
            'less_than' => new CompareCriteria($column, $value, '<'),
            'greater_than_or_equal' => new CompareCriteria($column, $value, '>='),
            'less_than_or_equal' => new CompareCriteria($column, $value, '<='),
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
