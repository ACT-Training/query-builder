# ACT Training Query Builder

- `act-training/query-builder` provides abstract Livewire components for building tables, query builders and reports over Eloquent models. Build these screens by extending one of its components rather than writing a table by hand.
- Check sibling components in the application that already extend these classes and follow their structure.

## Choosing a Component

- `ACTTraining\QueryBuilder\TableBuilder`: a simple table with search and URL-persisted filters. Implement `query()`, `columns()` and `filters()`.
- `ACTTraining\QueryBuilder\QueryBuilder`: a table with a user-editable AND/OR criteria builder. Implement `query()`, `columns()` and `conditions()`.
- `ACTTraining\QueryBuilder\ReportBuilder`: extends `QueryBuilder`. Users pick columns at runtime, so implement `query()` and `availableColumns()` instead of `columns()`/`conditions()`. Set `public bool $enableGroupBy = true;` for grouping with aggregates (COUNT, SUM, AVG, MIN, MAX).
- Choose the report editor layout with `public string $columnLayout = 'sections';` (`'sections'`, `'tabs'` or `'accordion'`). Don't restyle the editor view to get tabs or an accordion.
- `ACTTraining\QueryBuilder\GroupsBuilder` is deprecated. Use `ReportBuilder` with `$enableGroupBy = true` instead.
- `query()` must return an `Illuminate\Database\Eloquent\Builder`. Eager load every relationship referenced by a column key with `->with([...])` to avoid N+1 queries.
- Configure behaviour in a `config(): void` method using the fluent setters, e.g. `$this->enableQueryBuilder()->rowClickable(false);`.

## Model Setup

- Models queried by `QueryBuilder` or `ReportBuilder` must use the `ACTTraining\QueryBuilder\Support\Concerns\AppliesCriteria` trait, which adds the `apply()` scope used by the criteria system.

## Columns, Conditions and Filters

- Every column, condition and filter is created with `::make($label, $key)`. When `$key` is omitted it defaults to `Str::snake($label)`, so pass `$key` explicitly whenever the attribute name differs.
- Keys use dot notation to traverse relationships, e.g. `contract.job.name`. The last segment is the column; the rest is the relationship path.
- Columns (`ACTTraining\QueryBuilder\Support\Columns`): `Column`, `BooleanColumn`, `DateColumn`, `ViewColumn`. Common modifiers: `->sortable()`, `->searchable()`, `->justify('left'|'center'|'right')`, `->component('view.name')`, `->reformatUsing(fn ($value) => ...)`, `->withSubTitle(fn ($row) => ...)`, `->hideIf($condition)`, `->hideHeader()`. `DateColumn` adds `->format()` and `->humanDiff()`.
- Conditions (`ACTTraining\QueryBuilder\Support\Conditions`, for `QueryBuilder`): `TextCondition`, `NumberCondition`, `FloatCondition`, `BooleanCondition`, `DateCondition`, `EnumCondition` (use `->options([...])`; `->multiple()` adds `is_any_of` and `is_none_of`, whose value is a list), `NullCondition`.
- Filters (`ACTTraining\QueryBuilder\Support\Filters`, for `TableBuilder`): `TextFilter`, `NumberFilter`, `DateFilter`, `BooleanFilter`, `SelectFilter` (use `->options([...])`), `NullFilter`.
- A table sorts only by the key of a `->sortable()` column, or by the default sort it sets with `setSort()` in `mount()` or `config()`. It ignores any other `sortBy`, and any direction other than `asc` or `desc`. `QueryBuilder` and `ReportBuilder` ignore criteria on a column that isn't a condition (a `ReportBuilder` also accepts any key in `availableColumns()`), and a report ignores a group or total column that isn't in `availableColumns()`.
- If your own code reads `$this->sortBy` (an export, a queued job, an `orderByRelated()` override), call `$this->ensureValidSort()` first. In an `orderByRelated()` override, check the relation with `ACTTraining\QueryBuilder\Support\RelationGuard::isRelation($model, $name)` before you call it.
- Do not mix the two systems: conditions belong to `QueryBuilder`/`ReportBuilder`, filters belong to `TableBuilder`.

## Footer Totals

- For totals, give columns a footer rather than calculating values in the view: `->footerLabel('Total')`, `->sum()`, `->avg()`, `->min()`, `->max()`, `->count()`, or `->footer(fn (Builder $query, $column) => ...)` for anything custom. The aggregate methods accept an optional formatting closure, e.g. `->sum(fn ($value) => number_format($value))`.
- Footer values cover every row matching the current search, filters and criteria, not just the current page. `footer()` callbacks receive a fresh copy of that query with ordering removed, scoped to the base model.
- Aggregates support the model's own columns and keys one `belongsTo`/`hasOne`/`morphOne` relationship away. Use `footer()` for deeper or `hasMany` relationships.
- A grouped `ReportBuilder` adds a grand-total footer by itself for COUNT and SUM (none for AVG, MIN or MAX). Don't add one by hand.
- In `ReportBuilder::availableColumns()`, set `'footer' => 'sum'` (or `avg`, `min`, `max`, `count`) or `'footerLabel' => 'Total'` on a column definition.

## Bulk Actions

- Return `Action::make($label, $key, fn (Enumerable $models) => ...)` from `actions()`. The callback receives only the selected rows within the current search, filters and criteria; after "Select all" that's every matching row on every page. Don't re-query or re-apply the filters yourself.
- Add `->lazy()` to an action that may run on thousands of rows: the callback then gets a `LazyCollection` loaded 500 rows at a time in key order (not the table's sort order), so type its parameter as `Enumerable`. `->standalone()` actions run without a selection and get an empty collection.
- Changing the search, filters, criteria or saved view clears the selection. `selectedRowsCount()` gives the number of rows an action would act on.

## Saved Views

- `TableBuilder` and `QueryBuilder` support per-user saved views (save, update, rename, delete, share, favourite, default). Turn them on in `config()` with `$this->enableSavedViews('a-unique-table-key')`. Don't change the key once people have saved views. `ReportBuilder` doesn't support them.
- They need `livewire/flux` and the package's migration (`php artisan vendor:publish --tag="query-builder-migrations"`). Don't build your own saved-views UI or table.
- To store views elsewhere, implement `ACTTraining\QueryBuilder\Support\Contracts\SavedViewRepository` and set `query-builder.saved_views.repository`.
- To convert Filament Advanced Tables views, call `ACTTraining\QueryBuilder\Support\SavedViews\AdvancedTablesImporter::importFromDatabase($tables, $mapState)` with a closure mapping Filament's `filters` array to saved state (`filters` for a TableBuilder; `criteria`, `andOr` and `columns` for a QueryBuilder). It can be run more than once.

## ReportBuilder Columns

- `availableColumns()` returns an array of groups, each a list of column definitions keyed by `label` and `key` (both required). Optional keys: `type` (`text` default, `number`, `float`, `boolean`, `date`, `enum`, `null`, `view`), `sortable`, `justify`, `view`, `options` (for `enum`), `skipCondition`, `footer` and `footerLabel`.

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
