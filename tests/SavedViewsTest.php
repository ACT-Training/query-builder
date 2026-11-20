<?php

use ACTTraining\QueryBuilder\Models\SavedViewPreference;
use ACTTraining\QueryBuilder\Models\SavedViewRecord;
use ACTTraining\QueryBuilder\QueryBuilder;
use ACTTraining\QueryBuilder\Support\Columns\Column;
use ACTTraining\QueryBuilder\Support\Concerns\AppliesCriteria;
use ACTTraining\QueryBuilder\Support\Conditions\NumberCondition;
use ACTTraining\QueryBuilder\Support\Conditions\TextCondition;
use ACTTraining\QueryBuilder\Support\Contracts\SavedViewRepository;
use ACTTraining\QueryBuilder\Support\Filters\SelectFilter;
use ACTTraining\QueryBuilder\Support\Filters\TextFilter;
use ACTTraining\QueryBuilder\Support\SavedViews\EloquentSavedViewRepository;
use ACTTraining\QueryBuilder\TableBuilder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\GenericUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

/**
 * Saved table views (pipeline2#33): people save a table's filters, search,
 * sort, columns and rows per page, share them, favourite them and pick a default.
 */
class SavedViewEmployee extends Model
{
    use AppliesCriteria;

    protected $table = 'saved_view_employees';

    protected $guarded = [];

    public $timestamps = false;
}

class SavedViewsTable extends TableBuilder
{
    public array $rowOptions = [10, 25, 50];

    public function config(): void
    {
        $this->enableSavedViews('employees');
    }

    public function query(): Builder
    {
        return SavedViewEmployee::query();
    }

    public function columns(): array
    {
        return [
            Column::make('Name', 'name')->searchable()->sortable(),
            Column::make('Department', 'department')->sortable(),
            Column::make('Salary', 'salary'),
        ];
    }

    public function filters(): array
    {
        return [
            SelectFilter::make('Department')->withOptions(['IT' => 'IT', 'HR' => 'HR']),
            TextFilter::make('Name'),
        ];
    }
}

class OtherSavedViewsTable extends SavedViewsTable
{
    public function config(): void
    {
        $this->enableSavedViews('other-employees');
    }
}

class PlainTable extends SavedViewsTable
{
    public function config(): void {}
}

class SavedViewsQueryTable extends QueryBuilder
{
    public function config(): void
    {
        parent::config();

        $this->enableSavedViews('employee-query');
    }

    public function query(): Builder
    {
        return SavedViewEmployee::query();
    }

    public function columns(): array
    {
        return [
            Column::make('Name', 'name')->searchable()->sortable(),
            Column::make('Department', 'department'),
            Column::make('Salary', 'salary'),
        ];
    }

    public function conditions(): array
    {
        return [
            TextCondition::make('Department', 'department'),
            NumberCondition::make('Salary', 'salary'),
        ];
    }
}

class PlainQueryTable extends SavedViewsQueryTable
{
    public function config(): void
    {
        $this
            ->displaySearch()
            ->displayToolbar()
            ->displayColumnSelector();
    }
}

function signInAs(int $id): void
{
    test()->actingAs(new GenericUser(['id' => $id]));
}

function repository(): SavedViewRepository
{
    return app(SavedViewRepository::class);
}

beforeEach(function () {
    (include __DIR__.'/../database/migrations/create_query_builder_saved_views_table.php.stub')->up();

    Schema::create('saved_view_employees', function ($table) {
        $table->id();
        $table->string('name');
        $table->string('department');
        $table->integer('salary');
    });

    SavedViewEmployee::insert([
        ['name' => 'Alice', 'department' => 'IT', 'salary' => 30000],
        ['name' => 'Bob', 'department' => 'HR', 'salary' => 20000],
        ['name' => 'Carol', 'department' => 'IT', 'salary' => 25000],
    ]);
});

describe('tables that have not opted in', function () {
    it('show no saved views and keep their existing behaviour', function () {
        signInAs(1);

        $component = Livewire::test(PlainTable::class);

        expect($component->instance()->hasSavedViews())->toBeFalse();
        $component->assertDontSee('Save as a new view');
    });

    it('still show every column again on the next request in a QueryBuilder', function () {
        signInAs(1);

        Livewire::test(PlainQueryTable::class)
            ->set('displayColumns', ['name'])
            ->call('sort', 'name')
            ->assertSet('displayColumns', ['name', 'department', 'salary']);
    });

    it('are off for guests', function () {
        $component = Livewire::test(SavedViewsTable::class);

        expect($component->instance()->hasSavedViews())->toBeFalse();
        expect(fn () => $component->instance()->applySavedView(1))->toThrow(AuthorizationException::class);
    });
});

describe('a TableBuilder', function () {
    it('saves the filters, search, sort and rows per page', function () {
        signInAs(1);

        Livewire::test(SavedViewsTable::class)
            ->set('filterValues', ['Department:=' => 'IT', 'Name:like' => null])
            ->set('searchBy', 'Al')
            ->call('sort', 'name')
            ->set('perPage', 25)
            ->set('savedViewName', 'IT people')
            ->call('saveView')
            ->assertHasNoErrors()
            ->assertDispatched('modal-close');

        $view = SavedViewRecord::sole();

        expect($view->user_id)->toBe(1)
            ->and($view->table_key)->toBe('employees')
            ->and($view->name)->toBe('IT people')
            ->and($view->is_shared)->toBeFalse()
            ->and($view->state)->toMatchArray([
                'search' => 'Al',
                'sort' => ['by' => 'name', 'direction' => 'asc'],
                'perPage' => 25,
                'filters' => ['Department:=' => 'IT'],
            ]);
    });

    it('needs a name to save a view', function () {
        signInAs(1);

        Livewire::test(SavedViewsTable::class)
            ->set('savedViewName', '  ')
            ->call('saveView')
            ->assertHasErrors('savedViewName');

        expect(SavedViewRecord::count())->toBe(0);
    });

    it('applies a saved view', function () {
        signInAs(1);

        $view = repository()->create(1, 'employees', 'HR', [
            'search' => '',
            'sort' => ['by' => 'department', 'direction' => 'desc'],
            'perPage' => 50,
            'filters' => ['Department:=' => 'HR'],
        ]);

        Livewire::test(SavedViewsTable::class)
            ->set('searchBy', 'something')
            ->call('applySavedView', $view->id)
            ->assertSet('activeSavedViewId', $view->id)
            ->assertSet('searchBy', '')
            ->assertSet('sortBy', 'department')
            ->assertSet('sortDirection', 'desc')
            ->assertSet('perPage', 50)
            ->assertSet('filterValues', ['Department:=' => 'HR', 'Name:like' => null])
            ->assertSee('Bob')
            ->assertDontSee('Alice');
    });

    it('drops anything the table no longer has', function () {
        signInAs(1);

        $view = repository()->create(1, 'employees', 'Old', [
            'sort' => ['by' => 'salary', 'direction' => 'desc'],
            'perPage' => 1000,
            'filters' => ['Department:=' => 'IT', 'Removed:=' => 'x'],
        ]);

        Livewire::test(SavedViewsTable::class)
            ->call('applySavedView', $view->id)
            ->assertSet('sortBy', '')
            ->assertSet('perPage', 10)
            ->assertSet('filterValues', ['Department:=' => 'IT', 'Name:like' => null]);
    });

    it('updates a view with the current state', function () {
        signInAs(1);

        $view = repository()->create(1, 'employees', 'Mine', ['filters' => ['Department:=' => 'IT']]);

        Livewire::test(SavedViewsTable::class)
            ->call('applySavedView', $view->id)
            ->set('filterValues.Department:=', 'HR')
            ->call('updateSavedView', $view->id);

        expect(SavedViewRecord::find($view->id)->state['filters'])->toBe(['Department:=' => 'HR']);
    });

    it('only applies views saved for the same table', function () {
        signInAs(1);

        $view = repository()->create(1, 'other-employees', 'Elsewhere', []);

        expect(fn () => Livewire::test(SavedViewsTable::class)->instance()->applySavedView($view->id))
            ->toThrow(AuthorizationException::class);
    });
});

describe('a QueryBuilder', function () {
    it('saves the criteria and columns and rebuilds them when applied', function () {
        signInAs(1);

        Livewire::test(SavedViewsQueryTable::class)
            ->call('addCriteria')
            ->set('criteria.0.value', 'IT')
            ->call('setAndOr', 'or')
            ->set('displayColumns', ['name', 'salary'])
            ->set('savedViewName', 'IT')
            ->call('saveView');

        $view = SavedViewRecord::sole();

        expect($view->state)->toMatchArray([
            'criteria' => [['column' => 'department', 'operation' => 'equals', 'value' => 'IT', 'extraValue' => null]],
            'andOr' => 'or',
            'columns' => ['name', 'salary'],
        ]);

        $component = Livewire::test(SavedViewsQueryTable::class)
            ->call('applySavedView', $view->id)
            ->assertSet('andOr', 'or')
            ->assertSet('displayColumns', ['name', 'salary'])
            ->assertSee('Alice')
            ->assertDontSee('Bob');

        expect($component->get('criteria'))->toBe([[
            'column' => 'department',
            'operation' => 'equals',
            'value' => 'IT',
            'extraValue' => null,
            'requiresExtra' => false,
            'displayValue' => true,
            'inputType' => 'text',
            'factor' => null,
            'options' => [],
        ]]);
    });

    it('keeps the chosen columns on later requests', function () {
        signInAs(1);

        Livewire::test(SavedViewsQueryTable::class)
            ->set('displayColumns', ['name'])
            ->call('sort', 'name')
            ->assertSet('displayColumns', ['name'])
            ->call('allColumns')
            ->assertSet('displayColumns', ['name', 'department', 'salary']);
    });

    it('drops criteria for conditions or operations the table no longer has', function () {
        signInAs(1);

        $view = repository()->create(1, 'employee-query', 'Old', [
            'criteria' => [
                ['column' => 'removed', 'operation' => 'equals', 'value' => 'x'],
                ['column' => 'department', 'operation' => 'no_such_operation', 'value' => 'x'],
                ['column' => 'salary', 'operation' => 'greater_than', 'value' => 21000],
            ],
            'columns' => ['removed'],
        ]);

        $component = Livewire::test(SavedViewsQueryTable::class)
            ->call('applySavedView', $view->id)
            ->assertSet('displayColumns', ['name', 'department', 'salary'])
            ->assertSee('Alice')
            ->assertDontSee('Bob');

        expect($component->get('criteria'))->toHaveCount(1)
            ->and($component->get('criteria.0.column'))->toBe('salary');
    });
});

describe('default views', function () {
    it('apply when the table first loads', function () {
        signInAs(1);

        $view = repository()->create(1, 'employees', 'HR', ['filters' => ['Department:=' => 'HR']]);
        repository()->setDefault(1, 'employees', $view->id);

        Livewire::test(SavedViewsTable::class)
            ->assertSet('activeSavedViewId', $view->id)
            ->assertSet('filterValues.Department:=', 'HR')
            ->assertSee('Bob')
            ->assertDontSee('Alice');
    });

    it('apply the chosen columns in a QueryBuilder', function () {
        signInAs(1);

        $view = repository()->create(1, 'employee-query', 'Names', ['columns' => ['name']]);
        repository()->setDefault(1, 'employee-query', $view->id);

        Livewire::test(SavedViewsQueryTable::class)
            ->assertSet('displayColumns', ['name'])
            ->call('sort', 'name')
            ->assertSet('displayColumns', ['name']);
    });

    it('do not apply when the URL has filters or a search', function () {
        signInAs(1);

        $view = repository()->create(1, 'employees', 'HR', ['filters' => ['Department:=' => 'HR']]);
        repository()->setDefault(1, 'employees', $view->id);

        Livewire::withQueryParams(['s' => 'Alice'])
            ->test(SavedViewsTable::class)
            ->assertSet('activeSavedViewId', null)
            ->assertSet('searchBy', 'Alice');

        Livewire::withQueryParams(['f' => ['Department:=' => 'IT']])
            ->test(SavedViewsTable::class)
            ->assertSet('activeSavedViewId', null);
    });

    it('are one per person per table', function () {
        signInAs(1);

        $first = repository()->create(1, 'employees', 'First', []);
        $second = repository()->create(1, 'employees', 'Second', []);
        $otherTable = repository()->create(1, 'other-employees', 'Other', []);
        repository()->setDefault(1, 'other-employees', $otherTable->id);

        Livewire::test(SavedViewsTable::class)
            ->call('toggleDefaultSavedView', $first->id)
            ->call('toggleDefaultSavedView', $second->id);

        expect(repository()->defaultFor(1, 'employees')->id)->toBe($second->id)
            ->and(repository()->defaultFor(1, 'other-employees')->id)->toBe($otherTable->id);

        Livewire::test(SavedViewsTable::class)->call('toggleDefaultSavedView', $second->id);

        expect(repository()->defaultFor(1, 'employees'))->toBeNull();
    });
});

describe('owners', function () {
    it('can rename, share and delete their views', function () {
        signInAs(1);

        $view = repository()->create(1, 'employees', 'Mine', []);

        $component = Livewire::test(SavedViewsTable::class)
            ->call('applySavedView', $view->id)
            ->call('renameSavedView', $view->id, '  Renamed ')
            ->call('toggleSavedViewShared', $view->id);

        expect(SavedViewRecord::find($view->id))
            ->name->toBe('Renamed')
            ->is_shared->toBeTrue();

        $component->call('deleteSavedView', $view->id)
            ->assertSet('activeSavedViewId', null);

        expect(SavedViewRecord::count())->toBe(0);
    });

    it('can save a view as shared', function () {
        signInAs(1);

        Livewire::test(SavedViewsTable::class)
            ->set('savedViewName', 'For everyone')
            ->set('savedViewShared', true)
            ->call('saveView');

        expect(SavedViewRecord::sole()->is_shared)->toBeTrue();
    });
});

describe('other people', function () {
    it('cannot see a view that is not shared', function () {
        $view = repository()->create(1, 'employees', 'Private', []);

        signInAs(2);

        $component = Livewire::test(SavedViewsTable::class);

        expect($component->instance()->savedViews())->toBe([])
            ->and(fn () => $component->instance()->applySavedView($view->id))->toThrow(AuthorizationException::class);
    });

    it('can apply, favourite and default a shared view', function () {
        $view = repository()->create(1, 'employees', 'Team view', ['filters' => ['Department:=' => 'HR']], shared: true);

        signInAs(2);

        Livewire::test(SavedViewsTable::class)
            ->assertSee('Team view')
            ->call('applySavedView', $view->id)
            ->assertSet('filterValues.Department:=', 'HR')
            ->call('toggleSavedViewFavourite', $view->id)
            ->call('toggleDefaultSavedView', $view->id);

        $seenByTwo = repository()->find($view->id, 2);
        $seenByOwner = repository()->find($view->id, 1);

        expect($seenByTwo->isFavourite)->toBeTrue()
            ->and($seenByTwo->isDefault)->toBeTrue()
            ->and($seenByOwner->isFavourite)->toBeFalse()
            ->and($seenByOwner->isDefault)->toBeFalse();
    });

    it('cannot rename, share, update or delete a shared view', function (string $method, array $arguments) {
        $view = repository()->create(1, 'employees', 'Team view', [], shared: true);

        signInAs(2);

        $component = Livewire::test(SavedViewsTable::class);

        expect(fn () => $component->instance()->{$method}($view->id, ...$arguments))
            ->toThrow(AuthorizationException::class);

        expect(SavedViewRecord::find($view->id))
            ->name->toBe('Team view')
            ->is_shared->toBeTrue();
    })->with([
        'rename' => ['renameSavedView', ['Hijacked']],
        'share' => ['toggleSavedViewShared', []],
        'update' => ['updateSavedView', []],
        'delete' => ['deleteSavedView', []],
    ]);

    it('lose a view from their lists when its owner stops sharing it', function () {
        $view = repository()->create(1, 'employees', 'Team view', [], shared: true);
        repository()->favourite($view->id, 1, true);
        repository()->favourite($view->id, 2, true);
        repository()->setDefault(2, 'employees', $view->id);

        repository()->share($view->id, false);

        expect(repository()->find($view->id, 2))->toBeNull()
            ->and(repository()->defaultFor(2, 'employees'))->toBeNull()
            ->and(repository()->find($view->id, 1)->isFavourite)->toBeTrue();
    });
});

describe('the saved views bar', function () {
    it('lists favourites, my views and shared views', function () {
        $mine = repository()->create(1, 'employees', 'My IT view', []);
        repository()->favourite($mine->id, 1, true);
        repository()->create(2, 'employees', 'Shared HR view', [], shared: true);
        repository()->create(2, 'employees', 'Someone else private view', []);

        signInAs(1);

        Livewire::test(SavedViewsTable::class)
            ->assertSeeInOrder(['My IT view', 'My views', 'My IT view', 'Shared with everyone', 'Shared HR view'])
            ->assertSee('Save as a new view')
            ->assertSee('Manage views')
            ->assertDontSee('Someone else private view');
    });

    it('shows in a QueryBuilder', function () {
        signInAs(1);

        Livewire::test(SavedViewsQueryTable::class)->assertSee('Save as a new view');
    });
});

it('uses the repository the app binds in config', function () {
    $custom = new class extends EloquentSavedViewRepository {};
    app()->instance($custom::class, $custom);
    config()->set('query-builder.saved_views.repository', $custom::class);

    expect(app(SavedViewRepository::class))->toBe($custom);
});

it('removes preferences when a view is deleted', function () {
    $view = repository()->create(1, 'employees', 'Mine', [], shared: true);
    repository()->favourite($view->id, 2, true);

    repository()->delete($view->id);

    expect(SavedViewPreference::count())->toBe(0);
});
