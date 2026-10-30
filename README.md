# Query Builder for Laravel

Drop-in Livewire components for building query builders, tables, and reports on Eloquent models.

## Installation

```bash
composer require act-training/query-builder
```

Optionally, publish the views:

```bash
php artisan vendor:publish --tag="query-builder-views"
```

## Model Setup

Models should use the `AppliesCriteria` trait:

```php
use ACTTraining\QueryBuilder\Support\Criteria\AppliesCriteria;

class Employee extends Model
{
    use AppliesCriteria;
}
```

## Components

The package provides three main abstract Livewire components to extend:

| Component | Purpose |
|---|---|
| `QueryBuilder` | Full query builder with criteria-based AND/OR filtering |
| `TableBuilder` | Simpler table with URL-persisted filters and search |
| `ReportBuilder` | Dynamic column selection, save/load/export, optional groupBy |

### QueryBuilder

Create a Livewire component that extends `QueryBuilder`. Define `query()`, `columns()`, and `conditions()`:

```php
<?php

namespace App\Livewire;

use ACTTraining\QueryBuilder\QueryBuilder;
use ACTTraining\QueryBuilder\Support\Columns\BooleanColumn;
use ACTTraining\QueryBuilder\Support\Columns\Column;
use ACTTraining\QueryBuilder\Support\Columns\DateColumn;
use ACTTraining\QueryBuilder\Support\Conditions\BooleanCondition;
use ACTTraining\QueryBuilder\Support\Conditions\DateCondition;
use ACTTraining\QueryBuilder\Support\Conditions\TextCondition;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Builder;

class EmployeesTable extends QueryBuilder
{
    public function config(): void
    {
        $this
            ->enableQueryBuilder()
            ->rowClickable(false);
    }

    public function query(): Builder
    {
        return Employee::query()->with(['contract', 'contract.job', 'contract.location']);
    }

    public function columns(): array
    {
        return [
            Column::make('Name', 'full_name')
                ->component('columns.common.title')
                ->sortable(),
            Column::make('Job Title', 'contract.job.name'),
            Column::make('Location', 'contract.location.name'),
            BooleanColumn::make('Line Manager', 'contract.line_manager')
                ->hideIf(false)
                ->justify('center'),
            DateColumn::make('Start Date', 'contract.start_date')
                ->humanDiff()
                ->justify('right'),
        ];
    }

    public function conditions(): array
    {
        return [
            TextCondition::make('Name', 'full_name'),
            TextCondition::make('Job Title', 'contract.job.name'),
            BooleanCondition::make('Line Manager', 'contract.line_manager'),
            TextCondition::make('Location', 'contract.location.name'),
            DateCondition::make('Start Date', 'contract.start_date'),
        ];
    }
}
```

### TableBuilder

A simpler alternative using filters instead of criteria. Define `query()`, `columns()`, and `filters()`:

```php
<?php

namespace App\Livewire;

use ACTTraining\QueryBuilder\TableBuilder;
use ACTTraining\QueryBuilder\Support\Columns\Column;
use ACTTraining\QueryBuilder\Support\Filters\TextFilter;
use ACTTraining\QueryBuilder\Support\Filters\SelectFilter;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Builder;

class EmployeesList extends TableBuilder
{
    public function query(): Builder
    {
        return Employee::query();
    }

    public function columns(): array
    {
        return [
            Column::make('Name', 'full_name')->sortable()->searchable(),
            Column::make('Department', 'department')->sortable(),
        ];
    }

    public function filters(): array
    {
        return [
            TextFilter::make('Name', 'full_name'),
            SelectFilter::make('Department', 'department')
                ->options(['HR' => 'HR', 'IT' => 'IT', 'Finance' => 'Finance']),
        ];
    }
}
```

### ReportBuilder

Extends `QueryBuilder` with dynamic column selection. Users pick columns at runtime from `availableColumns()`, and the component builds columns and conditions automatically:

```php
<?php

namespace App\Livewire;

use ACTTraining\QueryBuilder\ReportBuilder;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Builder;

class EmployeesReport extends ReportBuilder
{
    public function query(): Builder
    {
        return Employee::query()->with(['contract', 'contract.job', 'contract.location']);
    }

    public function availableColumns(): array
    {
        return [
            'Employee' => [
                ['label' => 'Name', 'key' => 'full_name'],
                ['label' => 'Email', 'key' => 'email'],
            ],
            'Contract' => [
                ['label' => 'Job Title', 'key' => 'contract.job.name'],
                ['label' => 'Location', 'key' => 'contract.location.name'],
                ['label' => 'Start Date', 'key' => 'contract.start_date', 'type' => 'date'],
                ['label' => 'Salary', 'key' => 'contract.salary', 'type' => 'number'],
                ['label' => 'Line Manager', 'key' => 'contract.line_manager', 'type' => 'boolean'],
            ],
        ];
    }
}
```

Column definitions in `availableColumns()` support these keys:

| Key | Description |
|---|---|
| `label` | Display label (required) |
| `key` | Column key, supports dot-notation for relationships (required) |
| `type` | Column type: `text` (default), `number`, `float`, `boolean`, `date`, `enum`, `null`, `view` |
| `sortable` | Enable sorting on this column |
| `justify` | Alignment: `left`, `center`, `right` |
| `view` | Custom Blade component for rendering |
| `options` | Options array for `enum` type |
| `skipCondition` | Exclude from query builder conditions |
| `footer` | Footer aggregate: `sum`, `avg`, `min`, `max` or `count` (see [Footer Totals](#footer-totals)) |
| `footerLabel` | Fixed footer text for this column, e.g. `Total` |

#### Report editor layout

Set `$columnLayout` to choose how the report editor lays out the groups from `availableColumns()`:

```php
public string $columnLayout = 'tabs'; // 'sections' (default), 'tabs' or 'accordion'
```

| Layout | Behaviour |
|---|---|
| `sections` | Every group listed under its own heading (the default) |
| `tabs` | One tab per group, plus a Group By tab when grouping is enabled |
| `accordion` | One collapsible panel per group, plus Group By; the first panel starts open |

Tabs and accordion panels show how many of their columns are selected. While someone types in the column search, both switch to showing every group that has a match. A report with only one section always uses `sections`.

### ReportBuilder with GroupBy

Enable groupBy to allow users to group results by a column with aggregate functions (COUNT, SUM, AVG, MIN, MAX). Set `$enableGroupBy = true`:

```php
<?php

namespace App\Livewire;

use ACTTraining\QueryBuilder\ReportBuilder;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Builder;

class EmployeesGroupReport extends ReportBuilder
{
    public bool $enableGroupBy = true;

    public function query(): Builder
    {
        return Employee::query()->with(['contract', 'contract.job', 'contract.location']);
    }

    public function availableColumns(): array
    {
        return [
            'Employee' => [
                ['label' => 'Name', 'key' => 'full_name'],
                ['label' => 'Department', 'key' => 'department'],
            ],
            'Contract' => [
                ['label' => 'Job Title', 'key' => 'contract.job.name'],
                ['label' => 'Location', 'key' => 'contract.location.name'],
                ['label' => 'Salary', 'key' => 'contract.salary', 'type' => 'number'],
            ],
        ];
    }
}
```

When `enableGroupBy` is true, the report editor UI shows additional controls:

- **Group By Column** - select which column to group by
- **Function** - aggregate function (COUNT, SUM, AVG, MIN, MAX)
- **Aggregate Column** - which column to aggregate (defaults to `id`, numeric columns from `availableColumns()` are included automatically)

An "Aggregate" column is automatically appended to the table when grouping is active. For COUNT and SUM, a footer row shows the grand total across all groups.

You can customise the available aggregate functions and groupable/aggregatable columns by overriding:

```php
public function aggregateFunctions(): array
{
    return ['COUNT', 'SUM', 'AVG'];
}

public function groupableColumns(): array
{
    // Return a flat array of ['label' => ..., 'key' => ...] items
    return [
        ['label' => 'Department', 'key' => 'department'],
        ['label' => 'Location', 'key' => 'contract.location.name'],
    ];
}

public function aggregatableColumns(): array
{
    return [
        ['label' => 'ID', 'key' => 'id'],
        ['label' => 'Salary', 'key' => 'contract.salary'],
    ];
}
```

## Columns

All column types use a fluent `make($label, $key)` constructor. If `$key` is omitted, it defaults to `Str::snake($label)`.

| Column Type | Description |
|---|---|
| `Column` | General text column |
| `BooleanColumn` | Boolean/checkbox display |
| `DateColumn` | Date formatting with `->format()` and `->humanDiff()` |
| `ViewColumn` | Custom Blade view rendering |

Columns support: `->sortable()`, `->searchable()`, `->justify('right')`, `->component('view.name')`, `->reformatUsing(callable)`, `->withSubTitle(callable)`, `->hideIf(condition)`, `->hideHeader()`.

## Footer Totals

Any column can show a value in a footer row at the bottom of the table. Footer values come from their own query over **every row matching the current search, filters and criteria**, not just the page on screen. This works in `TableBuilder`, `QueryBuilder` and `ReportBuilder`.

```php
public function columns(): array
{
    return [
        Column::make('Name', 'full_name')->footerLabel('Total'),
        Column::make('Salary', 'salary')->sum(fn ($value) => '£'.number_format($value)),
        Column::make('Hours', 'contract.hours')->avg(fn ($value) => round($value, 1)),
        Column::make('Headcount', 'id')->count(),
        Column::make('Leavers', 'id')->footer(
            fn (Builder $query) => $query->whereNotNull('left_at')->count()
        ),
    ];
}
```

| Method | Footer shows |
|---|---|
| `footerLabel('Total')` | Fixed text |
| `sum()`, `avg()`, `min()`, `max()` | That aggregate of the column, optionally passed through a formatting closure |
| `count()` | The number of matching rows |
| `footer(fn (Builder $query, $column) => ...)` | Whatever the closure returns. `$query` is a fresh copy of the table's filtered query with ordering removed |

Notes:

- Aggregates work on the model's own columns and on columns one `belongsTo`, `hasOne` or `morphOne` relationship away (`contract.hours`). For anything deeper, or for `hasMany`, use `footer()`.
- Footer values are plain text. They are not passed through `reformatUsing()` or the column's component, so format them in the closure.
- Only displayed columns are calculated, and a table with no footer columns runs no extra queries.
- There is no footer in card view.
- A grouped `ReportBuilder` shows a grand total automatically when the function is COUNT or SUM. Grouped AVG, MIN and MAX have no footer, because adding those up would mislead.

## Conditions (QueryBuilder)

Used with `QueryBuilder` and `ReportBuilder` for criteria-based filtering:

| Condition Type | Operations |
|---|---|
| `TextCondition` | equals, not_equals, contains, starts_with, ends_with |
| `NumberCondition` | equals, not_equals, greater_than, less_than, is_between |
| `FloatCondition` | equals, not_equals, greater_than, less_than, is_between |
| `BooleanCondition` | is_true, is_false |
| `DateCondition` | equals, before, after, is_between |
| `EnumCondition` | equals, not_equals (with defined options) |
| `NullCondition` | is_set, is_not_set |

## Filters (TableBuilder)

Used with `TableBuilder` for simpler key/operator/value filtering:

`TextFilter`, `NumberFilter`, `DateFilter`, `BooleanFilter`, `SelectFilter`, `NullFilter`

## Relationship Support

Dot-notation keys work throughout columns, conditions, and filters to traverse Eloquent relationships:

```php
Column::make('Job Title', 'contract.job.name')
TextCondition::make('Location', 'contract.location.name')
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Simon Barrett](https://github.com/ACT-Training)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
