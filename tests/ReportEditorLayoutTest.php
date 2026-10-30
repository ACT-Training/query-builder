<?php

use ACTTraining\QueryBuilder\ReportBuilder;
use ACTTraining\QueryBuilder\Support\Concerns\AppliesCriteria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Livewire\Livewire;

/**
 * The report editor lays out column groups as sections (the default), tabs or an
 * accordion (#73). Group By joins the tabs or accordion when grouping is enabled, and a
 * report with only one section falls back to the plain section layout.
 */
class TabsRecord extends Model
{
    use AppliesCriteria;

    protected $table = 'tabs_records';

    protected $guarded = [];

    public $timestamps = false;
}

class TabsReport extends ReportBuilder
{
    public string $columnLayout = 'tabs';

    public function query(): Builder
    {
        return TabsRecord::query();
    }

    public function columns(): array
    {
        return $this->buildColumns();
    }

    public function availableColumns(): array
    {
        return [
            'Employee' => [
                ['label' => 'Name', 'key' => 'name'],
                ['label' => 'Email', 'key' => 'email'],
            ],
            'Contract' => [
                ['label' => 'Salary', 'key' => 'salary', 'type' => 'number'],
                ['label' => 'Hours', 'key' => 'hours', 'type' => 'number'],
            ],
        ];
    }
}

class SingleGroupReport extends TabsReport
{
    public function availableColumns(): array
    {
        return [
            'Employee' => [
                ['label' => 'Name', 'key' => 'name'],
            ],
        ];
    }
}

class SingleGroupGroupByReport extends SingleGroupReport
{
    public bool $enableGroupBy = true;
}

beforeEach(function () {
    Schema::create('tabs_records', function ($table) {
        $table->id();
        $table->string('name');
        $table->string('email');
        $table->integer('salary');
        $table->integer('hours');
    });
});

class SectionsReport extends TabsReport
{
    public string $columnLayout = 'sections';

    public bool $enableGroupBy = true;
}

class AccordionReport extends TabsReport
{
    public string $columnLayout = 'accordion';

    public bool $enableGroupBy = true;
}

function editorHtml(string $component, array $selectedColumns = []): string
{
    $html = Livewire::test($component)
        ->set('selectedColumns', $selectedColumns)
        ->html();

    return Str::before(Str::after($html, 'x-data="{open: true'), 'wire:key="reset-button"');
}

function tabButtons(string $html): array
{
    preg_match_all('/<button[^>]*role="tab"[^>]*>(.*?)<\/button>/s', $html, $matches);

    return array_map(fn ($tab) => trim(preg_replace('/\s+/', ' ', strip_tags($tab))), $matches[1]);
}

it('shows a tab for each column group', function () {
    $html = editorHtml(TabsReport::class);

    expect($html)->toContain('role="tablist"')
        ->and(tabButtons($html))->toBe(['Employee', 'Contract'])
        ->and(substr_count($html, 'role="tabpanel"'))->toBe(2);
});

it('shows how many columns are selected in each tab', function () {
    $html = editorHtml(TabsReport::class, ['name', 'salary', 'hours']);

    expect(tabButtons($html))->toBe(['Employee 1', 'Contract 2']);
});

it('adds a Group By tab when grouping is enabled', function () {
    $report = new class extends TabsReport
    {
        public bool $enableGroupBy = true;
    };

    $html = editorHtml($report::class);

    expect(tabButtons($html))->toBe(['Employee', 'Contract', 'Group By'])
        ->and(substr_count($html, 'role="tabpanel"'))->toBe(3)
        ->and($html)->toContain('id="groupBy"');
});

it('has no tab bar when there is only one section', function () {
    $html = editorHtml(SingleGroupReport::class);

    expect($html)->not->toContain('role="tablist"')
        ->and($html)->not->toContain('role="tabpanel"')
        ->and($html)->toContain('>Employee</h6>')
        ->and($html)->toContain('value="name"');
});

it('uses tabs for one column group plus Group By', function () {
    $html = editorHtml(SingleGroupGroupByReport::class);

    expect(tabButtons($html))->toBe(['Employee', 'Group By']);
});

it('lists every group as a section when tabs are not enabled', function () {
    $html = editorHtml(SectionsReport::class);

    expect($html)->not->toContain('role="tablist"')
        ->and($html)->not->toContain('role="tabpanel"')
        ->and($html)->toContain('>Employee</h6>')
        ->and($html)->toContain('>Contract</h6>')
        ->and($html)->toContain('>Group By</h6>');
});

it('keeps the section layout by default', function () {
    $report = new class extends ReportBuilder
    {
        public function query(): Builder
        {
            return TabsRecord::query();
        }
    };

    expect($report->columnLayout)->toBe('sections');
});

function accordionHeaders(string $html): array
{
    preg_match_all('/<button[^>]*@click="toggle\\(([^)]*)\\)"[^>]*>(.*?)<\\/button>/s', $html, $matches);

    return array_map(fn ($header) => trim(preg_replace('/\\s+/', ' ', strip_tags($header))), $matches[2]);
}

it('shows each group as an accordion panel with selection counts', function () {
    $html = editorHtml(AccordionReport::class, ['email', 'hours']);

    expect(accordionHeaders($html))->toBe(['Employee 1', 'Contract 1', 'Group By'])
        ->and($html)->not->toContain('role="tablist"')
        ->and($html)->not->toContain('</h6>')
        ->and($html)->toContain('expanded: [0]')
        ->and($html)->toContain('id="groupBy"');
});

it('has no accordion when there is only one section', function () {
    $report = new class extends SingleGroupReport
    {
        public string $columnLayout = 'accordion';
    };

    $html = editorHtml($report::class);

    expect(accordionHeaders($html))->toBe([])
        ->and($html)->toContain('>Employee</h6>');
});

it('falls back to sections for an unknown layout', function () {
    $report = new class extends TabsReport
    {
        public string $columnLayout = 'carousel';
    };

    $html = editorHtml($report::class);

    expect($html)->not->toContain('role="tablist"')
        ->and(accordionHeaders($html))->toBe([])
        ->and($html)->toContain('>Employee</h6>')
        ->and($html)->toContain('>Contract</h6>');
});
