<?php

namespace ACTTraining\QueryBuilder\Support\Concerns;

use ACTTraining\QueryBuilder\Support\Collection\AllOfCriteriaCollection;
use ACTTraining\QueryBuilder\Support\Collection\CriteriaCollection;
use ACTTraining\QueryBuilder\Support\Collection\OneOfCriteriaCollection;
use ACTTraining\QueryBuilder\Support\Criteria\BooleanCriteria;
use ACTTraining\QueryBuilder\Support\Criteria\CompareCriteria;
use ACTTraining\QueryBuilder\Support\Criteria\DateCriteria;
use ACTTraining\QueryBuilder\Support\Criteria\LikeCriteria;
use ACTTraining\QueryBuilder\Support\Criteria\ManyCriteria;
use ACTTraining\QueryBuilder\Support\Criteria\NullCriteria;
use Illuminate\Support\Arr;
use Illuminate\Support\Enumerable;

trait WithQueryBuilder
{
    public array $criteria = [];

    public string $andOr = 'and';

    /** @noinspection PhpUndefinedMethodInspection */
    public function addCriteria(): void
    {
        $this->resetPage();
        $this->resetSelection();

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
            $this->resetValueWhenChoosingSeveralChanges($index, $value);
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

    /**
     * Whether the operation takes several choices ("is any of", "is none of").
     */
    public function operationTakesSeveralValues($operation): bool
    {
        return in_array($operation, ['is_any_of', 'is_none_of'], true);
    }

    public function displayExtraValueForOperation($value): bool
    {
        return in_array($value, ['is_between', 'is_not_between']);
    }

    public function removeCriteria($index): void
    {
        unset($this->criteria[$index]);
        $this->criteria = array_values($this->criteria);
        $this->resetSelection();
    }

    public function setAndOr($condition): void
    {
        $this->andOr = $condition;
        $this->resetSelection();
    }

    public function resetQuery(): void
    {
        $this->resetPage();
        $this->criteria = [];
        $this->resetSelection();
    }

    /**
     * A single choice can't stand for several, or the other way round, so the value is cleared when
     * the operation changes between the two kinds. Between "is any of" and "is none of" the choices stay.
     */
    protected function resetValueWhenChoosingSeveralChanges($index, $operation): void
    {
        $value = $this->criteria[$index]['value'] ?? null;
        $takesSeveral = $this->operationTakesSeveralValues($operation);

        if ($takesSeveral !== is_array($value)) {
            $this->criteria[$index]['value'] = $takesSeveral ? [] : null;
        }
    }

    /**
     * The criteria come from the browser: keep only those on a column that the
     * table offers.
     */
    protected function validCriteria(): array
    {
        $allowed = $this->allowedCriteriaColumns();

        return array_values(array_filter(
            $this->criteria,
            fn ($criterion) => is_array($criterion)
                && is_string($criterion['column'] ?? null)
                && in_array($criterion['column'], $allowed, true)
        ));
    }

    /**
     * The columns that people may add criteria on.
     */
    protected function allowedCriteriaColumns(): array
    {
        return $this->resolveConditions()->pluck('key')->all();
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
            $criteria = $this->validCriteria();
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
    ): CompareCriteria|LikeCriteria|BooleanCriteria|DateCriteria|NullCriteria|ManyCriteria|null {
        $column = $criteria['column'];
        $value = $criteria['value'] ?? null;
        $extraValue = $criteria['extraValue'] ?? null;
        $factor = $criteria['factor'] ?? null;

        if ($this->operationTakesSeveralValues($criteria['operation'])) {
            return $this->getManyCriteria($column, $criteria['operation'], $value);
        }

        // Several choices only mean something to "is any of" and "is none of".
        if (is_array($value)) {
            return null;
        }

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

    /**
     * "Is any of" or "is none of", or null until something is chosen. A single value counts as one choice.
     */
    private function getManyCriteria(string $column, string $operation, mixed $value): ?ManyCriteria
    {
        $values = collect(Arr::wrap($value))
            ->filter(fn ($choice) => is_scalar($choice) && (string) $choice !== '')
            ->map(fn ($choice) => (string) $choice)
            ->values()
            ->all();

        if ($values === []) {
            return null;
        }

        return new ManyCriteria($column, $values, $operation === 'is_none_of');
    }
}
