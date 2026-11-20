<?php

use ACTTraining\QueryBuilder\QueryBuilder;
use ACTTraining\QueryBuilder\Support\Actions\Action;
use ACTTraining\QueryBuilder\Support\Columns\Column;
use ACTTraining\QueryBuilder\Support\Concerns\AppliesCriteria;
use ACTTraining\QueryBuilder\Support\Conditions\TextCondition;
use ACTTraining\QueryBuilder\Support\Filters\SelectFilter;
use ACTTraining\QueryBuilder\TableBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Enumerable;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\LazyCollection;
use Livewire\Livewire;

/**
 * Bulk actions must act on the rows the person selected, and nothing else (#127).
 *
 * executeAction() used to run "Select all" against query(), the table's base query,
 * so a search or filter narrowed what the table showed and selected but not what the
 * action changed. It also needed $model to find the key name, which most tables
 * never set.
 */
class BulkActionRecord extends Model
{
    use AppliesCriteria;

    protected $table = 'bulk_action_records';

    protected $guarded = [];

    public $timestamps = false;
}

class BulkActionCustomKeyRecord extends Model
{
    protected $table = 'bulk_action_custom_key_records';

    protected $primaryKey = 'record_no';

    protected $guarded = [];

    public $timestamps = false;
}

class BulkActionSpy
{
    public static ?Enumerable $models = null;

    public static int $calls = 0;

    public static function record(Enumerable $models): void
    {
        static::$models = $models;
        static::$calls++;
    }

    public static function names(): array
    {
        return static::$models->pluck('name')->sort()->values()->all();
    }
}

// Deliberately leaves $model unset, as most tables do.
class BulkActionsTable extends TableBuilder
{
    public array $rowOptions = [10, 25, 50];

    public int $perPage = 10;

    public function config(): void
    {
        $this->displayFilters();
    }

    public function query(): Builder
    {
        return BulkActionRecord::query();
    }

    public function columns(): array
    {
        return [
            Column::make('Name', 'name')->searchable()->sortable(),
            Column::make('Category', 'category'),
        ];
    }

    public function filters(): array
    {
        return [
            SelectFilter::make('Category')->withOptions(['a' => 'A', 'b' => 'B']),
        ];
    }

    public function actions(): array
    {
        return [
            Action::make('Archive', 'archive', fn (Enumerable $models) => BulkActionSpy::record($models)),
            Action::make('Archive lazily', 'archive-lazily', fn (Enumerable $models) => BulkActionSpy::record($models))->lazy(),
            Action::make('Export everything', 'export', fn (Enumerable $models) => BulkActionSpy::record($models))->standalone(),
        ];
    }
}

class BulkActionsQueryTable extends QueryBuilder
{
    public int $perPage = 10;

    public function query(): Builder
    {
        return BulkActionRecord::query();
    }

    public function columns(): array
    {
        return [
            Column::make('Name', 'name')->searchable()->sortable(),
            Column::make('Category', 'category'),
        ];
    }

    public function conditions(): array
    {
        return [
            TextCondition::make('Category', 'category'),
        ];
    }

    public function actions(): array
    {
        return [
            Action::make('Archive', 'archive', fn (Enumerable $models) => BulkActionSpy::record($models)),
        ];
    }
}

class BulkActionsCustomKeyTable extends TableBuilder
{
    public int $perPage = 10;

    public function query(): Builder
    {
        return BulkActionCustomKeyRecord::query();
    }

    public function columns(): array
    {
        return [
            Column::make('Name', 'name')->searchable(),
        ];
    }

    public function actions(): array
    {
        return [
            Action::make('Archive', 'archive', fn (Enumerable $models) => BulkActionSpy::record($models)),
        ];
    }
}

beforeEach(function () {
    BulkActionSpy::$models = null;
    BulkActionSpy::$calls = 0;

    Schema::create('bulk_action_records', function ($table) {
        $table->id();
        $table->string('name');
        $table->string('category');
    });

    Schema::create('bulk_action_custom_key_records', function ($table) {
        $table->unsignedInteger('record_no')->primary();
        $table->string('name');
    });

    // 25 rows over 3 pages of 10: 2 called Reilly, 5 in category "b".
    $rows = [];

    foreach (range(1, 25) as $i) {
        $rows[] = [
            'name' => match ($i) {
                7 => 'Ann Reilly',
                19 => 'Tom Reilly',
                default => "Person {$i}",
            },
            'category' => $i % 5 === 0 ? 'b' : 'a',
        ];
    }

    BulkActionRecord::insert($rows);

    // Keys that don't line up with an `id` column or with the row order.
    BulkActionCustomKeyRecord::insert([
        ['record_no' => 901, 'name' => 'Alpha'],
        ['record_no' => 902, 'name' => 'Bravo Reilly'],
        ['record_no' => 903, 'name' => 'Charlie Reilly'],
        ['record_no' => 904, 'name' => 'Delta'],
    ]);
});

describe('Select all', function () {
    it('acts only on the rows a search left', function () {
        Livewire::test(BulkActionsTable::class)
            ->set('searchBy', 'Reilly')
            ->set('selectPage', true)
            ->call('toggleSelectAll')
            ->call('executeAction', 'archive');

        expect(BulkActionSpy::names())->toBe(['Ann Reilly', 'Tom Reilly']);
    });

    it('acts only on the rows a filter left, across every page', function () {
        Livewire::test(BulkActionsTable::class)
            ->set('filterValues', ['Category:=' => 'a'])
            ->set('selectPage', true)
            ->call('toggleSelectAll')
            ->call('executeAction', 'archive');

        expect(BulkActionSpy::$models)->toHaveCount(20)
            ->and(BulkActionSpy::$models->pluck('category')->unique()->all())->toBe(['a']);
    });

    it('acts only on the rows the criteria left in a QueryBuilder, across every page', function () {
        Livewire::test(BulkActionsQueryTable::class)
            ->call('addCriteria')
            ->set('criteria.0.operation', 'equals')
            ->set('criteria.0.value', 'a')
            ->set('selectPage', true)
            ->call('toggleSelectAll')
            ->call('executeAction', 'archive');

        expect(BulkActionSpy::$models)->toHaveCount(20)
            ->and(BulkActionSpy::$models->pluck('category')->unique()->all())->toBe(['a']);
    });

    it('acts only on the rows a search left in a QueryBuilder', function () {
        Livewire::test(BulkActionsQueryTable::class)
            ->set('searchBy', 'Reilly')
            ->set('selectPage', true)
            ->call('toggleSelectAll')
            ->call('executeAction', 'archive');

        expect(BulkActionSpy::names())->toBe(['Ann Reilly', 'Tom Reilly']);
    });

    it('acts on as many rows as the table says are selected', function () {
        $component = Livewire::test(BulkActionsTable::class)
            ->set('filterValues', ['Category:=' => 'a'])
            ->set('selectPage', true)
            ->call('toggleSelectAll');

        $shown = $component->instance()->selectedRowsCount();

        $component->assertSee("All {$shown} matching rows are selected.")
            ->call('executeAction', 'archive');

        expect($shown)->toBe(20)
            ->and(BulkActionSpy::$models)->toHaveCount($shown);
    });

    it('acts on every row when nothing narrows the table', function () {
        Livewire::test(BulkActionsTable::class)
            ->set('selectPage', true)
            ->call('toggleSelectAll')
            ->call('executeAction', 'archive');

        expect(BulkActionSpy::$models)->toHaveCount(25);
    });

    it('still lists the table rows after the action runs', function () {
        Livewire::test(BulkActionsTable::class)
            ->set('searchBy', 'Reilly')
            ->set('selectPage', true)
            ->call('toggleSelectAll')
            ->call('executeAction', 'archive')
            ->assertSee('Ann Reilly')
            ->assertSee('Tom Reilly')
            ->assertSet('selectAll', false)
            ->assertSet('selectedRows', []);
    });

    it('gives the callback an Eloquent collection unless the action asks for lazy loading', function () {
        Livewire::test(BulkActionsTable::class)
            ->set('selectPage', true)
            ->call('toggleSelectAll')
            ->call('executeAction', 'archive');

        expect(BulkActionSpy::$models)->toBeInstanceOf(EloquentCollection::class);
    });

    it('loads the rows in chunks for a lazy action', function () {
        Livewire::test(BulkActionsTable::class)
            ->set('filterValues', ['Category:=' => 'a'])
            ->set('selectPage', true)
            ->call('toggleSelectAll')
            ->call('executeAction', 'archive-lazily');

        expect(BulkActionSpy::$models)->toBeInstanceOf(LazyCollection::class)
            ->and(BulkActionSpy::$models->count())->toBe(20)
            ->and(BulkActionSpy::$models->pluck('category')->unique()->all())->toBe(['a']);
    });
});

describe('a manual selection', function () {
    it('acts on exactly the ticked rows', function () {
        $ids = BulkActionRecord::whereIn('name', ['Person 2', 'Person 21'])->pluck('id')->all();

        Livewire::test(BulkActionsTable::class)
            ->set('selectedRows', $ids)
            ->call('executeAction', 'archive');

        expect(BulkActionSpy::names())->toBe(['Person 2', 'Person 21']);
    });

    it('selects the current page by its keys', function () {
        $component = Livewire::test(BulkActionsTable::class)
            ->set('selectPage', true);

        expect($component->get('selectedRows'))->toHaveCount(10);

        $component->call('executeAction', 'archive');

        expect(BulkActionSpy::$models)->toHaveCount(10);
    });

    it('works on a table that has not set $model', function () {
        $id = BulkActionRecord::where('name', 'Person 3')->value('id');

        Livewire::test(BulkActionsTable::class)
            ->set('selectedRows', [$id])
            ->call('executeAction', 'archive');

        expect(BulkActionSpy::names())->toBe(['Person 3']);
    });

    it('does nothing when no rows are selected', function () {
        Livewire::test(BulkActionsTable::class)
            ->call('executeAction', 'archive');

        expect(BulkActionSpy::$calls)->toBe(0);
    });
});

describe('a model whose primary key is not id', function () {
    it('selects and acts on the right rows with Select all', function () {
        $component = Livewire::test(BulkActionsCustomKeyTable::class)
            ->set('searchBy', 'Reilly')
            ->set('selectPage', true);

        expect($component->get('selectedRows'))->toEqualCanonicalizing([902, 903]);

        $component->call('toggleSelectAll')
            ->call('executeAction', 'archive');

        expect(BulkActionSpy::names())->toBe(['Bravo Reilly', 'Charlie Reilly']);
    });

    it('acts on the ticked rows', function () {
        Livewire::test(BulkActionsCustomKeyTable::class)
            ->set('selectedRows', [901, 904])
            ->call('executeAction', 'archive');

        expect(BulkActionSpy::names())->toBe(['Alpha', 'Delta']);
    });

    it('uses the key as the checkbox value', function () {
        Livewire::test(BulkActionsCustomKeyTable::class)
            ->assertSeeHtml('value="903"');
    });
});

describe('a standalone action', function () {
    it('still gets no models', function () {
        Livewire::test(BulkActionsTable::class)
            ->set('selectPage', true)
            ->call('toggleSelectAll')
            ->call('executeAction', 'export');

        expect(BulkActionSpy::$models)->toBeEmpty();
    });
});

describe('the selection is cleared', function () {
    $selectAll = fn () => Livewire::test(BulkActionsTable::class)
        ->set('selectPage', true)
        ->call('toggleSelectAll')
        ->assertSet('selectAll', true);

    it('when the search changes', function () use ($selectAll) {
        $selectAll()->set('searchBy', 'Reilly')
            ->assertSet('selectAll', false)
            ->assertSet('selectPage', false)
            ->assertSet('selectedRows', []);
    });

    it('when the search is reset', function () use ($selectAll) {
        $selectAll()->call('resetSearch')
            ->assertSet('selectAll', false)
            ->assertSet('selectedRows', []);
    });

    it('when a filter changes', function () use ($selectAll) {
        $selectAll()->set('filterValues.Category:=', 'b')
            ->assertSet('selectAll', false)
            ->assertSet('selectedRows', []);
    });

    it('when the filters are reset', function () use ($selectAll) {
        $selectAll()->call('resetFilters')
            ->assertSet('selectAll', false)
            ->assertSet('selectedRows', []);
    });

    it('when a saved view is applied', function () use ($selectAll) {
        $selectAll()->call('applySavedViewState', ['search' => 'Reilly'])
            ->assertSet('selectAll', false)
            ->assertSet('selectedRows', []);
    });

    it('when the criteria change in a QueryBuilder', function () {
        $component = Livewire::test(BulkActionsQueryTable::class)
            ->set('selectPage', true)
            ->call('toggleSelectAll')
            ->assertSet('selectAll', true);

        $component->call('addCriteria')
            ->assertSet('selectAll', false)
            ->assertSet('selectedRows', []);

        $component->set('selectPage', true)->call('toggleSelectAll')
            ->set('criteria.0.value', 'b')
            ->assertSet('selectAll', false)
            ->assertSet('selectedRows', []);

        $component->set('selectPage', true)->call('toggleSelectAll')
            ->call('setAndOr', 'or')
            ->assertSet('selectAll', false);

        $component->set('selectPage', true)->call('toggleSelectAll')
            ->call('removeCriteria', 0)
            ->assertSet('selectAll', false);

        $component->call('addCriteria')->set('selectPage', true)->call('toggleSelectAll')
            ->call('resetQuery')
            ->assertSet('selectAll', false);
    });

    it('but not when the table is sorted or paged', function () use ($selectAll) {
        $selectAll()->call('sort', 'name')
            ->call('nextPage')
            ->assertSet('selectAll', true);
    });
});
