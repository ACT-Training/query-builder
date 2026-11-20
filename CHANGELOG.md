# Changelog

All notable changes to `query-builder` will be documented in this file.

## 3.1.1 - Unreleased

### Saved views fit the table and the style guide (pipeline2#33)

- **Placement.** The saved views menu now sits at the right of the search row, or of the toolbar or actions row when there is one, instead of a separate row above the table. Its menu opens from its right edge. With no search, toolbar or actions row, it gets a right-aligned row of its own.
- **Deleting a view** is confirmed in a Flux modal, not the browser's own dialog. When the last view is deleted, the Manage views panel closes too.
- **Works with any icon set.** Favourite, default and shared are shown by the button itself (filled, with `aria-pressed`) instead of a solid icon. Apps that replace Flux's icons with Tabler's, which has no solid style, no longer crash.
- The view name fields turn off autofill (`autocomplete="off"`, plus the 1Password, LastPass, Bitwarden and Dashlane opt-outs), so password managers no longer offer a person's name or login there.
- The menu uses the `queue-list` icon instead of a bookmark, and no longer draws two separators above its actions.
- Fixed a template compile error: the delete modals no longer carry `wire:key` on the `flux:modal` tag itself, which Livewire's automatic loop keys broke.

No upgrade steps. Apps that override `table.blade.php` or `query-table.blade.php` should copy the new saved views placement into their override.

## 3.1.0 - 2026-11-20

## What's New

### Saved table views (pipeline2#33)

`TableBuilder` and `QueryBuilder` tables can let people save the current filters (or criteria), search, sort, columns and rows per page as a named view. People can update, rename and delete their own views, share them with everyone who can open the table, favourite any view they can see, and pick a default view that's applied when the page opens without filters in the URL. Only a view's owner can change, share or delete it.

```php
public function config(): void
{
    $this->enableSavedViews('enquiries');
}
```

- Off unless a table calls `enableSavedViews()`. Existing tables don't change.
- Needs `livewire/flux` (free components) and the new publishable migration: `php artisan vendor:publish --tag="query-builder-migrations"`.
- Storage sits behind the `SavedViewRepository` contract, so an app can use its own table.
- `AdvancedTablesImporter` converts Filament Advanced Tables saved views, keeping owners, sharing and favourites, with the filter mapping supplied by the app. It can be run more than once.
- In a `QueryBuilder` with saved views turned on, the columns someone hides now stay hidden after they sort, search or filter. Tables without saved views still show every column again after each change, as before.

## 3.0.0 - 2026-10-30

## Breaking

### Laravel 11 is no longer supported (#121)

The package now requires Laravel 12 or 13 (`illuminate/contracts: ^12.0 || ^13.0`). Laravel 11 no longer receives security fixes, and Composer now refuses to install it because of unpatched security advisories. Apps on Laravel 11 stay on 2.x until they upgrade.

## What's New

### Totals footer row for tables (#122, closes #57)

Columns can show a value in a footer row aligned beneath them. Values are calculated from their own query over **every row matching the current search, filters and criteria**, not just the visible page. This works in `TableBuilder`, `QueryBuilder` and `ReportBuilder`.

```php
Column::make('Name', 'full_name')->footerLabel('Total'),
Column::make('Salary', 'salary')->sum(fn ($value) => '£'.number_format($value)),
Column::make('Hours', 'contract.hours')->avg(),
Column::make('Headcount', 'id')->count(),
Column::make('Leavers', 'id')->footer(fn (Builder $query) => $query->whereNotNull('left_at')->count()),
```

- `sum()`, `avg()`, `min()` and `max()` work on model columns and on keys one `belongsTo`, `hasOne` or `morphOne` relationship away. Use `footer()` for anything else.
- In `ReportBuilder::availableColumns()`, use `'footer' => 'sum'` (or `avg`, `min`, `max`, `count`) or `'footerLabel' => 'Total'`.
- Grouped reports show a grand total for COUNT and SUM.
- Tables without footer columns run no extra queries.

### Report editor layouts: sections, tabs or accordion (#123, closes #73)

Set `$columnLayout` on a `ReportBuilder` to choose how the column groups are laid out:

```php
public string $columnLayout = 'tabs'; // 'sections' (default), 'tabs' or 'accordion'
```

- Tabs and accordion panels show how many of their columns are selected. Group By gets its own tab or panel.
- The column search shows every group with a match while a term is typed.
- `sections` remains the default, so existing reports are unchanged.

### Laravel Boost guidelines (#120)

The package now ships AI guidelines in `resources/boost/guidelines`. Apps that use [Laravel Boost](https://laravel.com/docs/boost) pick them up on their next `php artisan boost:install`, which gives coding agents guidance on the builders, columns, conditions, filters and footers.

## Upgrading

- Upgrade the app to Laravel 12 or 13, then change its constraint to `"act-training/query-builder": "^3.0"`.
- If you have published the package views, re-publish them (`php artisan vendor:publish --tag="query-builder-views" --force`) or merge in the changes to `table`, `query-table`, `report-table` and `report-editor`. Otherwise the footer row and the new editor layouts won't appear.

## Maintenance

- CI fixed: tests run without a coverage driver, and the Laravel 11 legs were removed (#121)
- `actions/checkout` bumped to v7 (#117)

**Full Changelog**: https://github.com/ACT-Training/query-builder/compare/2.4.2...3.0.0
