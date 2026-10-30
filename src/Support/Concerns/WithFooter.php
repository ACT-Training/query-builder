<?php

namespace ACTTraining\QueryBuilder\Support\Concerns;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use InvalidArgumentException;

/**
 * Gives a column a value in the table's footer row.
 *
 * Footer values are calculated from their own query: every row matching the current
 * search, filters and criteria, not just the page on screen.
 */
trait WithFooter
{
    protected ?string $footerLabel = null;

    protected ?Closure $footerCallback = null;

    protected ?string $footerAggregate = null;

    protected ?Closure $footerFormatter = null;

    /**
     * Calculate the footer value yourself. The callback receives a fresh copy of the
     * table's filtered query (ordering removed) and the column.
     */
    public function footer(Closure $callback): static
    {
        $this->resetFooter();
        $this->footerCallback = $callback;

        return $this;
    }

    /**
     * Show fixed text in the footer, e.g. 'Total'.
     */
    public function footerLabel(string $label): static
    {
        $this->resetFooter();
        $this->footerLabel = $label;

        return $this;
    }

    public function sum(?Closure $format = null): static
    {
        return $this->footerAggregate('sum', $format);
    }

    public function avg(?Closure $format = null): static
    {
        return $this->footerAggregate('avg', $format);
    }

    public function min(?Closure $format = null): static
    {
        return $this->footerAggregate('min', $format);
    }

    public function max(?Closure $format = null): static
    {
        return $this->footerAggregate('max', $format);
    }

    /**
     * Count the rows matching the current search, filters and criteria.
     */
    public function count(?Closure $format = null): static
    {
        return $this->footerAggregate('count', $format);
    }

    public function hasFooter(): bool
    {
        return $this->footerLabel !== null
            || $this->footerCallback !== null
            || $this->footerAggregate !== null;
    }

    public function footerValue(Builder $query): mixed
    {
        if ($this->footerLabel !== null) {
            return $this->footerLabel;
        }

        if ($this->footerCallback !== null) {
            return call_user_func($this->footerCallback, $query, $this);
        }

        if ($this->footerAggregate === null) {
            return null;
        }

        $value = $this->aggregateFooterValue($query, $this->footerAggregate);

        // MySQL and Postgres return SUM and AVG as decimal strings.
        if (is_string($value) && is_numeric($value)) {
            $value += 0;
        }

        // avg, min and max of no rows are null: leave the cell blank rather than format nothing.
        if ($value === null || $this->footerFormatter === null) {
            return $value;
        }

        return call_user_func($this->footerFormatter, $value);
    }

    protected function footerAggregate(string $function, ?Closure $format): static
    {
        $this->resetFooter();
        $this->footerAggregate = $function;
        $this->footerFormatter = $format;

        return $this;
    }

    protected function resetFooter(): void
    {
        $this->footerLabel = null;
        $this->footerCallback = null;
        $this->footerAggregate = null;
        $this->footerFormatter = null;
    }

    protected function aggregateFooterValue(Builder $query, string $function): mixed
    {
        if ($function === 'count') {
            return $query->count();
        }

        $model = $query->getModel();
        $parts = explode('.', $this->key);
        $column = array_pop($parts);

        if (empty($parts)) {
            return $query->{$function}($model->qualifyColumn($column));
        }

        if (count($parts) > 1 || ! method_exists($model, $parts[0])) {
            throw new InvalidArgumentException("Cannot {$function} '{$this->key}' in the footer: only columns on the model or one relationship away are supported. Use ->footer(fn (\$query) => ...) instead.");
        }

        $relationName = $parts[0];
        $relation = $model->{$relationName}();

        if (! $relation instanceof BelongsTo && ! $relation instanceof HasOne && ! $relation instanceof MorphOne) {
            throw new InvalidArgumentException("Cannot {$function} '{$this->key}' in the footer: '{$relationName}' must be a belongsTo, hasOne or morphOne relationship. Use ->footer(fn (\$query) => ...) instead.");
        }

        // A correlated subquery gives one value per table row. Joining the related table
        // instead would make unqualified search and filter columns ambiguous.
        $rows = $query
            ->select($model->getQualifiedKeyName())
            ->withAggregate("{$relationName} as footer_value", $column, 'max');

        return $model->getConnection()
            ->query()
            ->fromSub($rows, 'footer_rows')
            ->{$function}('footer_value');
    }
}
