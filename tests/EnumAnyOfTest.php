<?php

use ACTTraining\QueryBuilder\QueryBuilder;
use ACTTraining\QueryBuilder\Support\Columns\Column;
use ACTTraining\QueryBuilder\Support\Concerns\AppliesCriteria;
use ACTTraining\QueryBuilder\Support\Conditions\EnumCondition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

/**
 * A list condition can let people pick several choices: "is any of" and "is none of" (pipeline2#65).
 * It's opt-in with ->multiple(), so existing tables keep just "is" and "is not".
 */
class AnyOfRecord extends Model
{
    use AppliesCriteria;

    protected $table = 'any_of_records';

    protected $guarded = [];

    public $timestamps = false;

    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(AnyOfLabel::class, 'any_of_label_record', 'record_id', 'label_id');
    }
}

class AnyOfLabel extends Model
{
    protected $table = 'any_of_labels';

    protected $guarded = [];

    public $timestamps = false;
}

class AnyOfTable extends QueryBuilder
{
    public function query(): Builder
    {
        return AnyOfRecord::query()->orderBy('name');
    }

    public function columns(): array
    {
        return [Column::make('Name', 'name')];
    }

    public function conditions(): array
    {
        return [
            EnumCondition::make('Colour', 'colour', ['red' => 'Red', 'green' => 'Green', 'blue' => 'Blue'])->multiple(),
            EnumCondition::make('Label', 'labels.id', ['1' => 'Urgent', '2' => 'Later', '3' => 'Spare'])->multiple(),
        ];
    }
}

beforeEach(function () {
    Schema::create('any_of_records', function ($table) {
        $table->id();
        $table->string('name');
        $table->string('colour')->nullable();
    });

    Schema::create('any_of_labels', function ($table) {
        $table->id();
        $table->string('name');
    });

    Schema::create('any_of_label_record', function ($table) {
        $table->unsignedBigInteger('record_id');
        $table->unsignedBigInteger('label_id');
    });

    foreach (['Urgent', 'Later', 'Spare'] as $label) {
        AnyOfLabel::create(['name' => $label]);
    }

    $records = [
        ['Apple', 'red', [1]],
        ['Basil', 'green', [2]],
        ['Cornflower', 'blue', [1, 2]],
        ['Dust', null, []],
    ];

    foreach ($records as [$name, $colour, $labels]) {
        AnyOfRecord::create(['name' => $name, 'colour' => $colour])->labels()->attach($labels);
    }
});

/**
 * The names the table shows with one criterion.
 *
 * @return list<string>
 */
function anyOfNames(string $column, string $operation, mixed $value): array
{
    $table = Livewire::test(AnyOfTable::class)
        ->call('addCriteria')
        ->set('criteria.0.column', $column)
        ->set('criteria.0.operation', $operation)
        ->set('criteria.0.value', $value);

    return $table->instance()->rowsQuery()->pluck('name')->all();
}

it('offers only "is" and "is not" unless the condition allows several choices', function () {
    expect(EnumCondition::make('Colour', 'colour', ['red' => 'Red'])->operations())
        ->toBe(['equals' => 'is', 'not_equals' => 'is not']);
});

it('adds "is any of" and "is none of" when the condition allows several choices', function () {
    expect(EnumCondition::make('Colour', 'colour', ['red' => 'Red'])->multiple()->operations())
        ->toBe(['equals' => 'is', 'not_equals' => 'is not', 'is_any_of' => 'is any of', 'is_none_of' => 'is none of']);
});

it('shows the rows that have any of the chosen values', function () {
    expect(anyOfNames('colour', 'is_any_of', ['red', 'blue']))->toBe(['Apple', 'Cornflower']);
});

it('leaves out the rows that have any of the chosen values', function () {
    expect(anyOfNames('colour', 'is_none_of', ['red', 'blue']))->toBe(['Basil']);
});

it('shows the rows with any of the chosen related records', function () {
    expect(anyOfNames('labels.id', 'is_any_of', ['2']))->toBe(['Basil', 'Cornflower']);
});

it('leaves out the rows with any of the chosen related records, keeping rows with none at all', function () {
    expect(anyOfNames('labels.id', 'is_none_of', ['1']))->toBe(['Basil', 'Dust']);
});

it('ignores the condition until something is chosen', function (mixed $value) {
    expect(anyOfNames('colour', 'is_any_of', $value))->toBe(['Apple', 'Basil', 'Cornflower', 'Dust']);
})->with([
    'nothing' => [null],
    'an empty choice' => [[]],
    'only blanks' => [['', null]],
]);

it('takes a single value as a choice of one', function () {
    expect(anyOfNames('colour', 'is_any_of', 'green'))->toBe(['Basil']);
});

it('clears the value when the test changes between one choice and several', function () {
    $table = Livewire::test(AnyOfTable::class)
        ->call('addCriteria')
        ->set('criteria.0.value', 'red')
        ->set('criteria.0.operation', 'is_any_of');

    expect($table->get('criteria.0.value'))->toBe([]);

    $table->set('criteria.0.value', ['red', 'blue'])->set('criteria.0.operation', 'equals');

    expect($table->get('criteria.0.value'))->toBeNull();
});

it('keeps the choices when the test changes between two that take several', function () {
    $table = Livewire::test(AnyOfTable::class)
        ->call('addCriteria')
        ->set('criteria.0.operation', 'is_any_of')
        ->set('criteria.0.value', ['red', 'blue'])
        ->set('criteria.0.operation', 'is_none_of');

    expect($table->get('criteria.0.value'))->toBe(['red', 'blue']);
});

it('offers "is any of" and "is none of" in the list of tests', function () {
    $condition = EnumCondition::make('Colour', 'colour', ['red' => 'Red'])->multiple();

    expect($condition->toArray()['operations'])->toHaveKeys(['is_any_of', 'is_none_of']);

    Livewire::test(AnyOfTable::class)
        ->call('addCriteria')
        ->assertSeeHtml('<option value="is_any_of">is any of</option>')
        ->assertSeeHtml('<option value="is_none_of">is none of</option>');
});

it('shows a list that takes several choices for "is any of"', function () {
    Livewire::test(AnyOfTable::class)
        ->call('addCriteria')
        ->set('criteria.0.operation', 'is_any_of')
        ->assertSeeHtmlInOrder(['id="value-0"', 'multiple', '</select>']);
});

it('shows a list that takes one choice for "is"', function () {
    $html = Livewire::test(AnyOfTable::class)->call('addCriteria')->html();

    expect(preg_match('/<select[^>]*id="value-0"[^>]*>/s', $html, $select))->toBe(1)
        ->and($select[0])->not->toContain('multiple');
});

it('ignores several choices given to a test that takes one, rather than failing', function () {
    expect(anyOfNames('colour', 'equals', ['red', 'blue']))->toBe(['Apple', 'Basil', 'Cornflower', 'Dust']);
});

it('opens a saved view with "is any of", keeping its choices', function () {
    $table = Livewire::test(AnyOfTable::class);
    $table->instance()->applySavedViewState(['criteria' => [
        ['column' => 'colour', 'operation' => 'is_any_of', 'value' => ['red', 'blue']],
        ['column' => 'labels.id', 'operation' => 'is_none_of', 'value' => '2'],
    ]]);

    expect($table->instance()->criteria[0]['value'])->toBe(['red', 'blue'])
        ->and($table->instance()->criteria[1]['value'])->toBe(['2'])
        ->and($table->instance()->rowsQuery()->pluck('name')->all())->toBe(['Apple']);
});
