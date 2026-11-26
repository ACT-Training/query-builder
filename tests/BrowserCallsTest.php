<?php

use ACTTraining\QueryBuilder\QueryBuilder;
use ACTTraining\QueryBuilder\ReportBuilder;
use ACTTraining\QueryBuilder\Support\Columns\Column;
use ACTTraining\QueryBuilder\Support\Concerns\AppliesCriteria;
use ACTTraining\QueryBuilder\Support\RelationGuard;
use ACTTraining\QueryBuilder\TableBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Lazy;
use Livewire\Exceptions\MethodNotFoundException;
use Livewire\Exceptions\NonPublicComponentMethodCall;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Livewire\Mechanisms\HandleComponents\HandleComponents;

/**
 * The methods that set a table up (its searchable keys, its default sort, its
 * pagination and so on) are for the table's own code. The browser can't call them,
 * and can't change the settings that decide which query a report runs.
 */
class BrowserCallsRecord extends Model
{
    use AppliesCriteria;
    use SoftDeletes;

    protected $table = 'browser_calls_records';

    protected $guarded = [];

    public $timestamps = false;

    public static int $calls = 0;

    public function owner(): BelongsTo
    {
        return $this->belongsTo(BrowserCallsRecord::class, 'id');
    }

    public function countCall(): int
    {
        static::$calls++;

        return static::$calls;
    }
}

class BrowserCallsTable extends TableBuilder
{
    public function query(): Builder
    {
        return BrowserCallsRecord::query();
    }

    public function config(): void
    {
        $this->usePagination(false)->additionalSearchables(['notes']);
    }

    public function columns(): array
    {
        return [Column::make('Title', 'title')->sortable()->searchable()];
    }
}

#[Lazy]
class BrowserCallsLazyTable extends BrowserCallsTable {}

/** A lazy table whose own action sets the sort from what the browser sends. */
#[Lazy]
class BrowserCallsLazyActionTable extends BrowserCallsTable
{
    public function mount(): void
    {
        $this->setSort('id', 'desc');
    }

    public function chooseSort(string $key): void
    {
        $this->setSort($key);
    }
}

class BrowserCallsQueryTable extends QueryBuilder
{
    public function query(): Builder
    {
        return BrowserCallsRecord::query();
    }

    public function config(): void
    {
        $this->usePagination(false);
    }

    public function columns(): array
    {
        return [Column::make('Title', 'title')->sortable()->searchable()];
    }
}

class BrowserCallsReport extends ReportBuilder
{
    public array $selectedColumns = ['title'];

    public function query(): Builder
    {
        return BrowserCallsRecord::query();
    }

    public function config(): void
    {
        $this->usePagination(false);
    }

    public function columns(): array
    {
        return $this->buildColumns();
    }

    public function conditions(): array
    {
        return $this->buildConditions();
    }

    public function availableColumns(): array
    {
        return [
            'Record' => [
                ['label' => 'Title', 'key' => 'title', 'sortable' => true, 'view' => 'columns.column'],
                ['label' => 'Hidden', 'key' => 'hidden_value', 'skipCondition' => true],
            ],
        ];
    }
}

beforeEach(function () {
    BrowserCallsRecord::$calls = 0;

    Schema::create('browser_calls_records', function ($table) {
        $table->id();
        $table->string('title');
        $table->string('notes');
        $table->string('hidden_value');
        $table->softDeletes();
    });

    BrowserCallsRecord::insert([
        ['id' => 1, 'title' => 'Bravo', 'notes' => 'second', 'hidden_value' => 'x'],
        ['id' => 2, 'title' => 'Alpha', 'notes' => 'first', 'hidden_value' => 'y'],
    ]);
});

it('does not let the browser call the methods that set a table up', function (string $method, array $params) {
    Livewire::test(BrowserCallsTable::class)->call($method, ...$params);
})->throws(NonPublicComponentMethodCall::class)->with([
    'searchable keys' => ['additionalSearchables', [['countCall.title']]],
    'default sort' => ['setSort', ['countCall.title']],
    'pagination' => ['usePagination', [false]],
    'page name' => ['pageName', ['other']],
    'saved views' => ['enableSavedViews', ['other-table']],
    'start-up hook' => ['callWithSorting', ['__lazyLoad']],
    'start-up window' => ['initializeWithSorting', []],
]);

it('does not let the browser call cache() on a query builder', function () {
    Livewire::test(BrowserCallsQueryTable::class)->call('cache', 'phpversion');
})->throws(MethodNotFoundException::class);

it('does not run a callback given to cache() from outside the component', function () {
    expect(method_exists(BrowserCallsQueryTable::class, 'cache'))->toBeTrue()
        ->and((new ReflectionMethod(BrowserCallsQueryTable::class, 'cache'))->isPublic())->toBeFalse();
});

it('does not use a searchable key that the browser sends', function () {
    rescue(fn () => Livewire::test(BrowserCallsTable::class)
        ->set('searchBy', 'a')
        ->call('additionalSearchables', ['countCall.title']), report: false);

    expect(BrowserCallsRecord::$calls)->toBe(0);
});

it('still searches the keys the table sets up itself', function () {
    $component = Livewire::test(BrowserCallsTable::class)->set('searchBy', 'first');

    expect($component->instance()->rowsQuery()->pluck('title')->all())->toBe(['Alpha']);
});

it('does not record a default sort that the browser sets before a lazy table loads', function () {
    $component = Livewire::test(BrowserCallsLazyTable::class);

    preg_match("/__lazyLoad\\('([^']+)'\\)/", html_entity_decode($component->html()), $matches);

    expect($matches)->not->toBeEmpty();

    [$snapshot] = app(HandleComponents::class)->update($component->snapshot, [], [
        ['method' => 'setSort', 'params' => ['hidden_value'], 'metadata' => []],
        ['method' => '__lazyLoad', 'params' => [$matches[1]], 'metadata' => []],
    ]);
})->throws(NonPublicComponentMethodCall::class);

it('does not record a sort that a table action sets before or while a lazy table loads', function (array $calls) {
    $component = Livewire::test(BrowserCallsLazyActionTable::class);

    preg_match("/__lazyLoad\\('([^']+)'\\)/", html_entity_decode($component->html()), $matches);

    expect($matches)->not->toBeEmpty();

    $calls = array_map(fn (string $method) => $method === '__lazyLoad'
        ? ['method' => '__lazyLoad', 'params' => [$matches[1]], 'metadata' => []]
        : ['method' => 'chooseSort', 'params' => ['hidden_value'], 'metadata' => []], $calls);

    [$snapshot] = app(HandleComponents::class)->update($component->snapshot, [], $calls);

    $loaded = in_array('__lazyLoad', array_column($calls, 'method'), true);

    // Loading records the table's own default from mount(), and nothing else.
    expect($snapshot['data']['defaultSortBy'])->toBe($loaded ? 'id' : '')
        ->and($snapshot['data']['sortBy'])->not->toBe('hidden_value');
})->with([
    'before it loads' => [['chooseSort']],
    'before it loads, in the same request' => [['chooseSort', '__lazyLoad']],
    'after it loads, in the same request' => [['__lazyLoad', 'chooseSort']],
]);

it('does not let the browser turn on report grouping or the query builder', function (string $property) {
    Livewire::test(BrowserCallsReport::class)->set($property, true);
})->throws(CannotUpdateLockedPropertyException::class)->with(['enableGroupBy', 'enableQueryBuilder']);

it('ignores report criteria on a column that is not offered as a condition', function () {
    $component = Livewire::test(BrowserCallsReport::class);
    $component->instance()->criteria = [[
        'column' => 'hidden_value',
        'operation' => 'equals',
        'value' => 'y',
        'displayValue' => true,
    ]];

    expect($component->instance()->rowsQuery()->pluck('title')->all())->toHaveCount(2);
});

it('does not treat methods that Laravel adds to a model as relationships', function (string $method) {
    expect(RelationGuard::isRelation(new BrowserCallsRecord, $method))->toBeFalse();
})->with(['restore', 'forceDelete', 'trashed', 'delete', 'countCall']);

it('still treats a model relationship as a relationship', function () {
    expect(RelationGuard::isRelation(new BrowserCallsRecord, 'owner'))->toBeTrue();
});
