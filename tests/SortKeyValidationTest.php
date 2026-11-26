<?php

use ACTTraining\QueryBuilder\QueryBuilder;
use ACTTraining\QueryBuilder\ReportBuilder;
use ACTTraining\QueryBuilder\Support\Columns\Column;
use ACTTraining\QueryBuilder\Support\Concerns\AppliesCriteria;
use ACTTraining\QueryBuilder\Support\Conditions\TextCondition;
use ACTTraining\QueryBuilder\TableBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

/**
 * Sort keys, criteria columns and report grouping come from the browser. The table
 * must only use the keys that the component itself defines (its sortable columns, its
 * conditions, its report columns) and ignore anything else.
 */
class SortKeyAuthor extends Model
{
    use AppliesCriteria;

    protected $table = 'sort_key_authors';

    protected $guarded = [];

    public $timestamps = false;
}

class SortKeyPost extends Model
{
    use AppliesCriteria;

    protected $table = 'sort_key_posts';

    protected $guarded = [];

    public $timestamps = false;

    public static int $calls = 0;

    public function author(): BelongsTo
    {
        return $this->belongsTo(SortKeyAuthor::class, 'author_id');
    }

    /**
     * Not a relationship. The table must never call it.
     */
    public function countCall(): int
    {
        static::$calls++;

        return static::$calls;
    }
}

class SortKeyTable extends TableBuilder
{
    public function query(): Builder
    {
        return SortKeyPost::query();
    }

    public function config(): void
    {
        $this->usePagination(false);
    }

    public function columns(): array
    {
        return [
            Column::make('Title', 'title')->sortable(),
            Column::make('Author', 'author.name')->sortable(),
            Column::make('Notes', 'notes'),
        ];
    }

    public function filters(): array
    {
        return [];
    }

    public function exposeOrderByRelated(Builder $query, string $key): void
    {
        $this->orderByRelated($query, $key);
    }
}

/** A default sort set in config() on a key that is not a sortable column. */
class SortKeyConfigDefaultTable extends SortKeyTable
{
    public function config(): void
    {
        $this->usePagination(false)->setSort('id', 'desc');
    }
}

/** A default sort set once, in mount(). */
class SortKeyMountDefaultTable extends SortKeyTable
{
    public function mount(): void
    {
        $this->setSort('id', 'desc');
    }
}

class SortKeyQueryTable extends QueryBuilder
{
    public function query(): Builder
    {
        return SortKeyPost::query();
    }

    public function config(): void
    {
        $this->usePagination(false);
    }

    public function columns(): array
    {
        return [
            Column::make('Title', 'title')->sortable(),
            Column::make('Author', 'author.name')->sortable(),
            Column::make('Notes', 'notes'),
        ];
    }

    public function conditions(): array
    {
        return [
            TextCondition::make('Title', 'title'),
            TextCondition::make('Author', 'author.name'),
        ];
    }
}

class SortKeyReport extends ReportBuilder
{
    public bool $enableGroupBy = true;

    public array $selectedColumns = ['title', 'author.name'];

    public function query(): Builder
    {
        return SortKeyPost::query();
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
            'Post' => [
                ['label' => 'Title', 'key' => 'title', 'sortable' => true, 'view' => 'columns.column'],
                ['label' => 'Words', 'key' => 'words', 'type' => 'number', 'sortable' => true],
                ['label' => 'Category', 'key' => 'category'],
            ],
            'Author' => [
                ['label' => 'Author', 'key' => 'author.name', 'sortable' => true],
            ],
        ];
    }
}

beforeEach(function () {
    SortKeyPost::$calls = 0;

    Schema::create('sort_key_authors', function ($table) {
        $table->id();
        $table->string('name');
    });

    Schema::create('sort_key_posts', function ($table) {
        $table->id();
        $table->string('title');
        $table->string('category');
        $table->integer('words');
        $table->string('notes');
        $table->string('hidden_value');
        $table->foreignId('author_id');
    });

    SortKeyAuthor::insert([
        ['id' => 1, 'name' => 'Zara'],
        ['id' => 2, 'name' => 'Adam'],
        ['id' => 3, 'name' => 'Mia'],
    ]);

    SortKeyPost::insert([
        ['id' => 1, 'title' => 'Bravo', 'category' => 'news', 'words' => 100, 'notes' => 'b', 'hidden_value' => 'c', 'author_id' => 1],
        ['id' => 2, 'title' => 'Alpha', 'category' => 'news', 'words' => 300, 'notes' => 'c', 'hidden_value' => 'a', 'author_id' => 2],
        ['id' => 3, 'title' => 'Charlie', 'category' => 'blog', 'words' => 50, 'notes' => 'a', 'hidden_value' => 'b', 'author_id' => 3],
    ]);
});

function sortKeyTitles($component): array
{
    return $component->instance()->rowsQuery()->pluck('title')->all();
}

describe('TableBuilder', function () {
    it('ignores a relationship-style sort key that is not a sortable column', function () {
        $component = Livewire::test(SortKeyTable::class)->set('sortBy', 'countCall.title');

        expect(SortKeyPost::$calls)->toBe(0)
            ->and($component->get('sortBy'))->toBe('')
            ->and(SortKeyPost::count())->toBe(3);
    });

    it('ignores the same key when it comes through sort() or setSort()', function (string $method) {
        $component = Livewire::test(SortKeyTable::class)->call($method, 'countCall.title');

        expect(SortKeyPost::$calls)->toBe(0)
            ->and($component->get('sortBy'))->toBe('');
    })->with(['sort', 'setSort']);

    it('ignores a sort key that is not a sortable column', function (string $key) {
        $component = Livewire::test(SortKeyTable::class)->set('sortBy', $key);

        expect($component->get('sortBy'))->toBe('')
            ->and($component->instance()->rowsQuery()->toSql())->not->toContain('order by');
    })->with(['hidden_value', 'notes']);

    it('checks a sort set on a table made outside Livewire, as a queued export does', function () {
        $table = new SortKeyConfigDefaultTable;
        $table->config();
        $table->sortBy = 'hidden_value';

        expect($table->rowsQuery()->toSql())->not->toContain('hidden_value')
            ->and($table->sortBy)->toBe('');

        $table->sortBy = 'author.name';
        $table->sortDirection = 'asc';

        expect($table->rowsQuery()->pluck('title')->all())->toBe(['Alpha', 'Charlie', 'Bravo']);
    });

    it('only accepts asc or desc as the sort direction', function () {
        $component = Livewire::test(SortKeyTable::class)
            ->set('sortBy', 'title')
            ->set('sortDirection', 'sideways');

        expect($component->get('sortDirection'))->toBe('asc')
            ->and(sortKeyTitles($component))->toBe(['Alpha', 'Bravo', 'Charlie']);
    });

    it('still sorts by a sortable column', function () {
        $component = Livewire::test(SortKeyTable::class)->call('sort', 'title')->call('sort', 'title');

        expect($component->get('sortDirection'))->toBe('desc')
            ->and(sortKeyTitles($component))->toBe(['Charlie', 'Bravo', 'Alpha']);
    });

    it('still sorts by a sortable relationship column', function () {
        $component = Livewire::test(SortKeyTable::class)->set('sortBy', 'author.name');

        expect(sortKeyTitles($component))->toBe(['Alpha', 'Charlie', 'Bravo']);
    });

    it('keeps a default sort set in config() across requests', function () {
        $component = Livewire::test(SortKeyConfigDefaultTable::class);

        expect(sortKeyTitles($component))->toBe(['Charlie', 'Alpha', 'Bravo']);

        $component->call('refreshTable');

        expect($component->get('sortBy'))->toBe('id')
            ->and(sortKeyTitles($component))->toBe(['Charlie', 'Alpha', 'Bravo']);
    });

    it('keeps a default sort set in mount() across requests', function () {
        $component = Livewire::test(SortKeyMountDefaultTable::class)->call('refreshTable');

        expect($component->get('sortBy'))->toBe('id')
            ->and(sortKeyTitles($component))->toBe(['Charlie', 'Alpha', 'Bravo']);
    });

    it('does not let the browser add a default sort through setSort()', function () {
        $component = Livewire::test(SortKeyMountDefaultTable::class)->call('setSort', 'hidden_value');

        expect($component->get('sortBy'))->toBe('');
    });

    it('does not let the browser change the recorded default sort', function () {
        Livewire::test(SortKeyMountDefaultTable::class)->set('defaultSortBy', 'hidden_value');
    })->throws(CannotUpdateLockedPropertyException::class);

    it('only calls relationship methods in orderByRelated()', function (string $key) {
        $query = SortKeyPost::query();

        (new SortKeyTable)->exposeOrderByRelated($query, $key);

        expect(SortKeyPost::$calls)->toBe(0)
            ->and(SortKeyPost::count())->toBe(3);
    })->with(['countCall.title', 'newQuery.title']);
});

describe('QueryBuilder', function () {
    it('ignores a relationship-style sort key that is not a sortable column', function () {
        $component = Livewire::test(SortKeyQueryTable::class)->set('sortBy', 'countCall.title');

        expect(SortKeyPost::$calls)->toBe(0)
            ->and($component->get('sortBy'))->toBe('');
    });

    it('ignores a sort key that is not a sortable column', function () {
        $component = Livewire::test(SortKeyQueryTable::class)->set('sortBy', 'hidden_value');

        expect($component->get('sortBy'))->toBe('')
            ->and($component->instance()->rowsQuery()->toSql())->not->toContain('hidden_value');
    });

    it('still sorts by a sortable column and a sortable relationship column', function () {
        $component = Livewire::test(SortKeyQueryTable::class)->call('sort', 'title');

        expect(sortKeyTitles($component))->toBe(['Alpha', 'Bravo', 'Charlie']);

        $component->call('sort', 'author.name');

        expect(sortKeyTitles($component))->toBe(['Alpha', 'Charlie', 'Bravo']);
    });

    it('ignores a criterion on a column that is not a condition', function (string $column) {
        $component = Livewire::test(SortKeyQueryTable::class);
        // Set directly, as a saved or restored state would.
        $component->instance()->criteria = [[
            'column' => $column,
            'operation' => 'equals',
            'value' => 'a',
            'displayValue' => true,
        ]];

        expect(SortKeyPost::$calls)->toBe(0)
            ->and(sortKeyTitles($component))->toHaveCount(3);
    })->with(['countCall.title', 'hidden_value']);

    it('still applies a criterion on a condition', function () {
        $component = Livewire::test(SortKeyQueryTable::class);
        // Set directly, as a saved or restored state would.
        $component->instance()->criteria = [[
            'column' => 'author.name',
            'operation' => 'equals',
            'value' => 'Mia',
            'displayValue' => true,
        ]];

        expect(sortKeyTitles($component))->toBe(['Charlie']);
    });
});

describe('ReportBuilder', function () {
    it('ignores a relationship-style sort key that is not a sortable column', function () {
        $component = Livewire::test(SortKeyReport::class)->set('sortBy', 'countCall.title');

        expect(SortKeyPost::$calls)->toBe(0)
            ->and($component->get('sortBy'))->toBe('');
    });

    it('still sorts by a sortable relationship column', function () {
        $component = Livewire::test(SortKeyReport::class)->call('sort', 'author.name');

        expect(sortKeyTitles($component))->toBe(['Alpha', 'Charlie', 'Bravo']);
    });

    it('ignores a group by key that is not a report column', function (string $key) {
        $component = Livewire::test(SortKeyReport::class)->set('groupBy', $key);

        $sql = $component->instance()->rowsQuery()->toSql();

        expect(SortKeyPost::$calls)->toBe(0)
            ->and($sql)->not->toContain('group by')
            ->and($sql)->not->toContain('hidden_value');
    })->with(['hidden_value', 'countCall.title']);

    it('ignores a total column that is not a number column of the report', function (string $key) {
        $component = Livewire::test(SortKeyReport::class)
            ->set('groupBy', 'category')
            ->set('aggregateFunction', 'SUM')
            ->set('aggregateColumn', $key);

        $sql = $component->instance()->rowsQuery()->toSql();

        expect(SortKeyPost::$calls)->toBe(0)
            ->and($sql)->not->toContain('hidden_value')
            ->and($sql)->not->toContain('countCall');
    })->with(['hidden_value', 'countCall.title']);

    it('still groups and sorts by the total', function () {
        $component = Livewire::test(SortKeyReport::class)
            ->set('groupBy', 'category')
            ->set('aggregateFunction', 'SUM')
            ->set('aggregateColumn', 'words')
            ->call('sort', 'aggregate')
            ->call('sort', 'aggregate');

        expect($component->instance()->rowsQuery()->get()->map->only('group_value', 'aggregate')->all())
            ->toBe([
                ['group_value' => 'news', 'aggregate' => 400],
                ['group_value' => 'blog', 'aggregate' => 50],
            ]);
    });

    it('still applies a criterion on a report column that is not displayed', function () {
        $component = Livewire::test(SortKeyReport::class);
        // Set directly, as a saved or restored state would.
        $component->instance()->criteria = [[
            'column' => 'category',
            'operation' => 'equals',
            'value' => 'blog',
            'displayValue' => true,
        ]];

        expect(sortKeyTitles($component))->toBe(['Charlie']);
    });
});
