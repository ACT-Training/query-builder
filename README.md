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
| `EnumCondition` | equals, not_equals (with defined options); with `->multiple()`, also is_any_of, is_none_of |
| `NullCondition` | is_set, is_not_set |

### Several choices

`->multiple()` on an `EnumCondition` adds "is any of" and "is none of", where people pick several choices. The criterion's `value` is then a list:

```php
EnumCondition::make('Stage', 'pipeline_stage_id', $stages)->multiple();

// a saved or preset criterion
['column' => 'pipeline_stage_id', 'operation' => 'is_any_of', 'value' => ['1', '4']]
```

- "Is any of" shows the rows whose value is one of the choices. On a related record (`tags.id`), it shows rows with any of them.
- "Is none of" leaves those rows out. On a column, rows with no value are left out too, as with "is not". On a related record, rows with no related record at all are kept.
- A criterion with nothing chosen is ignored. A single value counts as one choice.
- Changing the test between one that takes a single choice and one that takes several clears the value.

## Filters (TableBuilder)

Used with `TableBuilder` for simpler key/operator/value filtering:

`TextFilter`, `NumberFilter`, `DateFilter`, `BooleanFilter`, `SelectFilter`, `NullFilter`

## Bulk Actions

`TableBuilder` and `QueryBuilder` tables can offer actions on the rows people tick. Return them from `actions()`:

```php
use ACTTraining\QueryBuilder\Support\Actions\Action;
use Illuminate\Support\Enumerable;

public function actions(): array
{
    return [
        Action::make('Archive', 'archive', fn (Enumerable $models) => $models->each->archive()),
        Action::make('Export all', 'export', fn () => $this->export())->standalone(),
    ];
}
```

- The callback receives the selected rows, always within the table's current search, filters and criteria. After "Select all" that's every matching row on every page, and nothing outside them. Changing the search, a filter, the criteria or the saved view clears the selection.
- By default the callback gets an Eloquent collection. For actions that may run on thousands of rows, add `->lazy()` to get a `LazyCollection` that loads them 500 at a time, in key order rather than the table's sort order (`->lazy(1000)` for a different chunk size). Type the callback's parameter as `Enumerable` or `LazyCollection`, not `Collection`.
- A `->standalone()` action runs without a selection and gets an empty collection.
- `$this->selectedRowsCount()` returns how many rows an action would act on.

## Saved Views

`TableBuilder` and `QueryBuilder` tables can let people save what they're looking at as a named view: the filters (or criteria), search, sort, columns and rows per page. Each person can:

- save the current state as a new view, and update a view they own with what the table shows now
- rename or delete views they own
- share a view they own with everyone who can open the table, or stop sharing it
- favourite any view they can see (their own or a shared one); favourites show as quick buttons above the table
- make any view they can see their default for that table; it's applied when the page opens without filters or a search in the URL

Only a view's owner can rename, update, share or delete it. When an owner stops sharing a view, it disappears from other people's favourites and defaults. Saved views are off unless a table turns them on, and they're hidden from guests. `ReportBuilder` doesn't support them yet.

### Set up

1. Saved views use [Flux](https://fluxui.dev) (its free components). Install it if your app doesn't have it: `composer require livewire/flux`.
2. Publish and run the migration (two tables: `query_builder_saved_views` and `query_builder_saved_view_user`):

    ```bash
    php artisan vendor:publish --tag="query-builder-migrations"
    php artisan migrate
    ```

    The migration stores user ids as big integers. If your users have UUID or ULID keys, change the two `user_id` columns in the published migration first.

3. Turn saved views on in the table's `config()`, with a key that's unique to the table. Views are stored against this key, so don't change it later.

    ```php
    public function config(): void
    {
        $this->enableSavedViews('enquiries');
    }
    ```

    In a `QueryBuilder`, call `parent::config()` first if you rely on its defaults.

When a view is applied, anything the table no longer has (a removed filter, condition, column or sort) is dropped, so old views keep working after a table changes.

### Your own storage

To store views somewhere else, write a class implementing `ACTTraining\QueryBuilder\Support\Contracts\SavedViewRepository` and set it in the published config:

```php
// config/query-builder.php
'saved_views' => [
    'repository' => App\Support\MySavedViewRepository::class,
],
```

### Importing views from Filament Advanced Tables

`AdvancedTablesImporter` converts Advanced Tables' saved views (`filament_filter_sets` and `filament_filter_set_user`) into saved views. It carries across each view's owner, name, search, sort, sharing and favourites. Only your app knows how a Filament filter maps onto your new table, so you supply that mapping: a closure that receives the decoded Advanced Tables `filters` array (and the whole row) and returns the parts of the saved state it can fill in, or `null` to skip the view.

```php
use ACTTraining\QueryBuilder\Support\SavedViews\AdvancedTablesImporter;

$report = app(AdvancedTablesImporter::class)->importFromDatabase(
    tables: ['App\Filament\Resources\EnquiryResource' => 'enquiries'], // Advanced Tables resource => table key
    mapState: fn (array $filters, array $row) => [
        // TableBuilder: filter codes are "Label:operator"
        'filters' => array_filter([
            'Stage:=' => $filters['tableFilters']['pipeline_stage_id']['value'] ?? null,
        ]),
        // QueryBuilder instead: 'criteria' => [[column, operation, value, extraValue]], 'andOr' => 'and', 'columns' => [...]
    ],
);

$this->info("{$report->created} created, {$report->updated} updated, {$report->favourites} favourites");
collect($report->skipped)->each(fn ($reason, $id) => $this->warn("Skipped {$id}: {$reason}"));
collect($report->warnings)->each(fn ($warning) => $this->warn($warning));
```

- Public views stay shared. Rejected views come across as private.
- A global favourite becomes a shared view, but people have to favourite it themselves.
- Views based on a preset view, grouping or per-column searches are imported with a warning, because saved views can't do those.
- It's safe to run more than once (for a rehearsal and then the real switchover): it updates the views it imported before instead of duplicating them. A re-run overwrites an imported view's name, filters and sharing with the Advanced Tables data, so run it before people start using the new table. It only adds favourites to views it creates, so it never brings back a favourite someone removed. Use `import()` instead of `importFromDatabase()` to pass in rows you've read yourself, for example from another connection.

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
