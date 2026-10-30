<?php

use ACTTraining\QueryBuilder\QueryBuilder;
use ACTTraining\QueryBuilder\ReportBuilder;
use ACTTraining\QueryBuilder\Support\Actions\Action;
use ACTTraining\QueryBuilder\Support\Columns\Column;
use ACTTraining\QueryBuilder\Support\Concerns\AppliesCriteria;
use ACTTraining\QueryBuilder\TableBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Livewire\Livewire;

/**
 * A table footer shows values calculated from their own query (#57): every row
 * matching the current search, filters and criteria, not just the page on screen.
 */
class FooterContract extends Model
{
    protected $table = 'footer_contracts';

    protected $guarded = [];

    public $timestamps = false;
}

class FooterEmployee extends Model
{
    use AppliesCriteria;

    protected $table = 'footer_employees';

    protected $guarded = [];

    public $timestamps = false;

    public function contract(): BelongsTo
    {
        return $this->belongsTo(FooterContract::class, 'contract_id');
    }
}

class FooterTable extends TableBuilder
{
    public array $footerColumns = [];

    public function query(): Builder
    {
        return FooterEmployee::query()->where('active', true);
    }

    public function columns(): array
    {
        return [
            Column::make('Name', 'name')->searchable()->sortable()->footerLabel('Total'),
            Column::make('Salary', 'salary')->sortable()->sum(),
            Column::make('Hours', 'contract.hours')->sortable()->sum(),
            Column::make('Department', 'department'),
        ];
    }

    public function filters(): array
    {
        return [];
    }
}

class FooterTableWithActions extends FooterTable
{
    public function actions(): array
    {
        return [
            Action::make('Archive', 'archive', fn () => null),
        ];
    }
}

class FooterSharedKeyTable extends TableBuilder
{
    public function query(): Builder
    {
        return FooterEmployee::query()->where('active', true);
    }

    public function columns(): array
    {
        return [
            Column::make('Name', 'name')->footerLabel('Total'),
            Column::make('Headcount', 'id')->count(),
            Column::make('In HR', 'id')->footer(fn (Builder $query) => $query->where('department', 'HR')->count()),
        ];
    }

    public function filters(): array
    {
        return [];
    }
}

class FooterlessTable extends TableBuilder
{
    public function query(): Builder
    {
        return FooterEmployee::query();
    }

    public function columns(): array
    {
        return [
            Column::make('Name', 'name'),
            Column::make('Salary', 'salary'),
        ];
    }

    public function filters(): array
    {
        return [];
    }
}

class FooterQueryTable extends QueryBuilder
{
    public function query(): Builder
    {
        return FooterEmployee::query()->where('active', true);
    }

    public function columns(): array
    {
        return [
            Column::make('Name', 'name')->searchable()->footerLabel('Total'),
            Column::make('Salary', 'salary')->avg(fn ($value) => number_format($value, 2)),
            Column::make('Headcount', 'id')->count(),
        ];
    }

    public function conditions(): array
    {
        return [];
    }
}

class FooterReport extends ReportBuilder
{
    public function query(): Builder
    {
        return FooterEmployee::query()->where('active', true);
    }

    public function columns(): array
    {
        return $this->buildColumns();
    }

    public function availableColumns(): array
    {
        return [
            'Employee' => [
                ['label' => 'Name', 'key' => 'name', 'footerLabel' => 'Total'],
                ['label' => 'Department', 'key' => 'department'],
                ['label' => 'Salary', 'key' => 'salary', 'type' => 'number', 'footer' => 'sum'],
            ],
        ];
    }
}

beforeEach(function () {
    // Both tables have a `name` column, so joining contracts onto the filtered query
    // would make the unqualified search on `name` ambiguous.
    Schema::create('footer_contracts', function ($table) {
        $table->id();
        $table->string('name');
        $table->integer('hours');
    });

    Schema::create('footer_employees', function ($table) {
        $table->id();
        $table->string('name');
        $table->string('department');
        $table->integer('salary');
        $table->boolean('active');
        $table->foreignId('contract_id');
    });

    FooterContract::insert([
        ['id' => 1, 'name' => 'Full time', 'hours' => 37],
        ['id' => 2, 'name' => 'Part time', 'hours' => 20],
    ]);

    FooterEmployee::insert([
        ['name' => 'Alice', 'department' => 'IT', 'salary' => 30000, 'active' => true, 'contract_id' => 1],
        ['name' => 'Bob', 'department' => 'IT', 'salary' => 20000, 'active' => true, 'contract_id' => 2],
        ['name' => 'Carol', 'department' => 'HR', 'salary' => 25000, 'active' => true, 'contract_id' => 1],
        ['name' => 'Dave', 'department' => 'HR', 'salary' => 99000, 'active' => false, 'contract_id' => 1],
    ]);
});

it('totals every row matching the query, not just the current page', function () {
    $table = new FooterTable;
    $table->perPage = 1;

    expect($table->footerValues())->toBe([
        0 => 'Total',
        1 => 75000,
        2 => 94,
    ]);
});

it('narrows the totals to the search term', function () {
    $table = new FooterTable;
    $table->searchBy = 'a'; // Alice, Carol (and Dave, who is excluded by query())

    expect($table->footerValues())->toMatchArray([
        1 => 55000,
        2 => 74,
    ]);
});

it('drops ordering from the footer queries', function () {
    $table = new FooterTable;
    $table->sortBy = 'contract.hours';
    $table->sortDirection = 'desc';

    DB::enableQueryLog();
    $values = $table->footerValues();
    $queries = collect(DB::getQueryLog())->pluck('query');
    DB::disableQueryLog();

    expect($values[1])->toBe(75000)
        ->and($queries)->toHaveCount(2)
        ->and($queries->filter(fn ($sql) => str_contains(strtolower($sql), 'order by')))->toBeEmpty();
});

it('keeps separate values for footer columns that share a key', function () {
    expect((new FooterSharedKeyTable)->footerValues())->toBe([
        0 => 'Total',
        1 => 3,
        2 => 1,
    ]);

    $footer = Str::between(Livewire::test(FooterSharedKeyTable::class)->html(), '<tfoot', '</tfoot>');

    expect($footer)->toMatch('/>\\s*3\\s*</')->toMatch('/>\\s*1\\s*</');
});

it('rejects relationship keys more than one level deep', function () {
    $column = Column::make('Rate', 'contract.grade.rate')->sum();

    $column->footerValue(FooterEmployee::query());
})->throws(InvalidArgumentException::class, 'one relationship away');

it('runs no footer queries when no column has a footer', function () {
    $table = new FooterlessTable;

    DB::enableQueryLog();
    $hasFooter = $table->hasFooterRow();
    $values = $table->footerValues();
    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    expect($hasFooter)->toBeFalse()
        ->and($values)->toBe([])
        ->and($queries)->toBeEmpty();
});

it('formats aggregates and counts rows in a query builder', function () {
    $table = new FooterQueryTable;
    $table->displayColumns = ['name', 'salary', 'id'];

    expect($table->footerValues())->toBe([
        0 => 'Total',
        1 => '25,000.00',
        2 => 3,
    ]);
});

it('leaves a formatted average blank when no rows match', function () {
    $table = new FooterQueryTable;
    $table->displayColumns = ['name', 'salary', 'id'];
    $table->searchBy = 'nobody';

    expect($table->footerValues())->toBe([
        0 => 'Total',
        1 => null,
        2 => 0,
    ]);
});

it('skips footers for columns the user has hidden', function () {
    $table = new FooterQueryTable;
    $table->displayColumns = ['name', 'id'];

    expect($table->footerValues())->toBe([
        0 => 'Total',
        2 => 3,
    ]);
});

it('builds report footers from availableColumns()', function () {
    $report = new FooterReport;
    $report->selectedColumns = ['name', 'department', 'salary'];
    $report->displayColumns = ['name', 'department', 'salary'];

    expect($report->hasFooterRow())->toBeTrue()
        ->and($report->footerValues())->toBe([
            0 => 'Total',
            2 => 75000,
        ]);
});

function groupedFooterReport(string $function, string $column = 'id'): FooterReport
{
    $report = new FooterReport;
    $report->selectedColumns = ['name', 'department', 'salary'];
    $report->enableGroupBy = true;
    $report->groupBy = 'department';
    $report->aggregateFunction = $function;
    $report->aggregateColumn = $column;
    $report->displayColumns = ['group_value', 'aggregate'];

    return $report;
}

it('shows a grand total for a grouped count', function () {
    // IT: 2, HR: 1 (Dave is excluded by query())
    $report = groupedFooterReport('COUNT');

    expect($report->hasFooterRow())->toBeTrue()
        ->and($report->footerValues())->toBe([0 => 'Total', 1 => 3]);
});

it('shows a grand total for a grouped sum', function () {
    $report = groupedFooterReport('SUM', 'salary');

    expect($report->footerValues())->toBe([0 => 'Total', 1 => 75000]);
});

it('has no footer for grouped averages, minimums or maximums', function (string $function) {
    expect(groupedFooterReport($function, 'salary')->hasFooterRow())->toBeFalse();
})->with(['AVG', 'MIN', 'MAX']);

function footerCellsMatchHeaderCells(string $html): void
{
    $headerCells = substr_count(Str::between($html, '<thead', '</thead>'), '<th');
    $footer = Str::between($html, '<tfoot', '</tfoot>');

    expect($headerCells)->toBeGreaterThan(0)
        ->and(substr_count($footer, '<td'))->toBe($headerCells)
        ->and($footer)->toContain('Total');
}

it('renders one footer cell under each header cell in a table builder', function () {
    $html = Livewire::test(FooterTable::class)->html();

    footerCellsMatchHeaderCells($html);
    expect(Str::between($html, '<tfoot', '</tfoot>'))->toContain('75000')->toContain('94');
});

it('keeps footer cells aligned when a table builder shows row checkboxes', function () {
    $html = Livewire::test(FooterTableWithActions::class)->html();

    expect($html)->toContain('checkbox-select-page');
    footerCellsMatchHeaderCells($html);
});

it('keeps footer cells aligned when a query builder shows row checkboxes', function () {
    $html = Livewire::test(FooterQueryTable::class)->html();

    expect($html)->toContain('checkbox-select-page');
    footerCellsMatchHeaderCells($html);
});
