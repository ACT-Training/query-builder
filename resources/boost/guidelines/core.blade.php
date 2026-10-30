# ACT Training Query Builder

- `act-training/query-builder` provides abstract Livewire components for building tables, query builders and reports over Eloquent models. Build these screens by extending one of its components rather than writing a table by hand.
- Check sibling components in the application that already extend these classes and follow their structure.

## Choosing a Component

- `ACTTraining\QueryBuilder\TableBuilder`: a simple table with search and URL-persisted filters. Implement `query()`, `columns()` and `filters()`.
- `ACTTraining\QueryBuilder\QueryBuilder`: a table with a user-editable AND/OR criteria builder. Implement `query()`, `columns()` and `conditions()`.
- `ACTTraining\QueryBuilder\ReportBuilder`: extends `QueryBuilder`. Users pick columns at runtime, so implement `query()` and `availableColumns()` instead of `columns()`/`conditions()`. Set `public bool $enableGroupBy = true;` for grouping with aggregates (COUNT, SUM, AVG, MIN, MAX).
- `ACTTraining\QueryBuilder\GroupsBuilder` is deprecated. Use `ReportBuilder` with `$enableGroupBy = true` instead.
- `query()` must return an `Illuminate\Database\Eloquent\Builder`. Eager load every relationship referenced by a column key with `->with([...])` to avoid N+1 queries.
- Configure behaviour in a `config(): void` method using the fluent setters, e.g. `$this->enableQueryBuilder()->rowClickable(false);`.

## Model Setup

- Models queried by `QueryBuilder` or `ReportBuilder` must use the `ACTTraining\QueryBuilder\Support\Concerns\AppliesCriteria` trait, which adds the `apply()` scope used by the criteria system.

## Columns, Conditions and Filters

- Every column, condition and filter is created with `::make($label, $key)`. When `$key` is omitted it defaults to `Str::snake($label)`, so pass `$key` explicitly whenever the attribute name differs.
- Keys use dot notation to traverse relationships, e.g. `contract.job.name`. The last segment is the column; the rest is the relationship path.
- Columns (`ACTTraining\QueryBuilder\Support\Columns`): `Column`, `BooleanColumn`, `DateColumn`, `ViewColumn`. Common modifiers: `->sortable()`, `->searchable()`, `->justify('left'|'center'|'right')`, `->component('view.name')`, `->reformatUsing(fn ($value) => ...)`, `->withSubTitle(fn ($row) => ...)`, `->hideIf($condition)`, `->hideHeader()`. `DateColumn` adds `->format()` and `->humanDiff()`.
- Conditions (`ACTTraining\QueryBuilder\Support\Conditions`, for `QueryBuilder`): `TextCondition`, `NumberCondition`, `FloatCondition`, `BooleanCondition`, `DateCondition`, `EnumCondition` (use `->options([...])`), `NullCondition`.
- Filters (`ACTTraining\QueryBuilder\Support\Filters`, for `TableBuilder`): `TextFilter`, `NumberFilter`, `DateFilter`, `BooleanFilter`, `SelectFilter` (use `->options([...])`), `NullFilter`.
- Do not mix the two systems: conditions belong to `QueryBuilder`/`ReportBuilder`, filters belong to `TableBuilder`.

## ReportBuilder Columns

- `availableColumns()` returns an array of groups, each a list of column definitions keyed by `label` and `key` (both required). Optional keys: `type` (`text` default, `number`, `float`, `boolean`, `date`, `enum`, `null`, `view`), `sortable`, `justify`, `view`, `options` (for `enum`) and `skipCondition`.

@boostsnippet("TableBuilder component", "php")
<?php

namespace App\Livewire;

use ACTTraining\QueryBuilder\Support\Columns\Column;
use ACTTraining\QueryBuilder\Support\Columns\DateColumn;
use ACTTraining\QueryBuilder\Support\Filters\SelectFilter;
use ACTTraining\QueryBuilder\Support\Filters\TextFilter;
use ACTTraining\QueryBuilder\TableBuilder;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Builder;

class EmployeesList extends TableBuilder
{
    public function query(): Builder
    {
        return Employee::query()->with(['contract.job']);
    }

    public function columns(): array
    {
        return [
            Column::make('Name', 'full_name')->sortable()->searchable(),
            Column::make('Job Title', 'contract.job.name'),
            DateColumn::make('Start Date', 'contract.start_date')->humanDiff()->justify('right'),
        ];
    }

    public function filters(): array
    {
        return [
            TextFilter::make('Name', 'full_name'),
            SelectFilter::make('Department', 'department')->options(['HR' => 'HR', 'IT' => 'IT']),
        ];
    }
}
@endboostsnippet

## Views

- Views use the `query-builder::` prefix. Publish them with `php artisan vendor:publish --tag="query-builder-views"` only when the defaults genuinely need changing.
