# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Laravel package (`act-training/query-builder`) providing drop-in Livewire components for building query builders, tables, and reports on Eloquent models. Uses Spatie's `laravel-package-tools` for package scaffolding.

Namespace: `ACTTraining\QueryBuilder`

## Commands

```bash
composer test              # Run tests (Pest)
vendor/bin/pest --filter="test name"  # Run a single test
composer analyse           # Static analysis (PHPStan, level 5)
composer format            # Code formatting (Laravel Pint)
composer start             # Build and serve via testbench
```

## Architecture

### Core Builder Classes (src/)

Three abstract Livewire components that consuming apps extend:

- **`QueryBuilder`** — Full-featured query builder with criteria-based filtering (AND/OR conditions). Users define `query()`, `columns()`, and `conditions()` methods. Renders `query-table` view.
- **`TableBuilder`** — Simpler table with URL-persisted filters and search. Users define `query()`, `columns()`, and `filters()` methods. Renders `table` view.
- **`ReportBuilder`** extends QueryBuilder — Adds report-specific features via `WithReportBuilder`. Renders `report-table` view.
- **`GroupsBuilder`** extends QueryBuilder — Adds grouping via `WithGroupBuilder`. Renders `group-table` view.

### Two Filtering Systems

**Criteria System** (used by QueryBuilder): Conditions → Criteria → CriteriaCollection pipeline.
- `Conditions` (src/Support/Conditions/) — Define available filter operations for the UI (TextCondition, DateCondition, BooleanCondition, etc.)
- `Criteria` (src/Support/Criteria/) — Implement the actual query logic (CompareCriteria, LikeCriteria, DateCriteria, BooleanCriteria, NullCriteria). All extend `BaseCriteria` implementing `CriteriaInterface`.
- `CriteriaCollection` (src/Support/Collection/) — Composes criteria with AND (`AllOfCriteriaCollection`) or OR (`OneOfCriteriaCollection`) logic.
- Models must `use AppliesCriteria` trait to gain the `->apply()` scope.

**Filter System** (used by TableBuilder): Simpler key/operator/value filters.
- `Filters` (src/Support/Filters/) — TextFilter, DateFilter, BooleanFilter, NumberFilter, SelectFilter, NullFilter. All extend `BaseFilter`.
- Filter codes use Blade-safe operator aliases (gte/lte/gt/lt instead of `>=`/`<=`/`>`/`<`) to avoid HTML encoding issues in `wire:model`.

### Columns (src/Support/Columns/)

Column types: `Column`, `BooleanColumn`, `DateColumn`, `ViewColumn`. All extend `BaseColumn`. Fluent API with `make()` static constructor. Support sorting, searching, hiding, custom components, justify alignment, subtitles, and value reformatting.

### Behavior Traits (src/Support/Concerns/)

All builder functionality is composed via traits: `WithActions`, `WithCaching`, `WithColumns`, `WithFilters`, `WithPagination`, `WithQueryBuilder`, `WithRowClick`, `WithRowInjection`, `WithSearch`, `WithSelecting`, `WithSorting`, `WithToolbar`, `WithViews`, `WithIdentifier`, `WithIndicator`, `WithMessage`.

### Relationship Handling

Dot-notation keys (e.g., `contract.job.name`) are used throughout columns, conditions, and filters to traverse Eloquent relationships. The pattern `explode('.', $key)` → `array_pop()` for column name → `implode('.')` for relationship path → `whereHas()` is used consistently across BaseCriteria, TableBuilder, and BaseColumn.

## Testing

Uses Pest with Orchestra Testbench. Tests are in `tests/`. The `TestCase` base class registers the service provider and configures factory resolution. Architecture tests enforce no debugging functions (`dd`, `dump`, `ray`).

## Views

Blade views in `resources/views/`. Four Blade components registered in the service provider for column rendering. View prefix is `query-builder::`.

## Conventions

- All public API classes use `static make($label, $key)` fluent constructors
- `$key` defaults to `Str::snake($label)` when not provided
- PHPStan level 5 with Octane compatibility and model property checks enabled
- PHP 8.2+, Livewire v3
