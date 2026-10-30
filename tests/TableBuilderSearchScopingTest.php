<?php

use ACTTraining\QueryBuilder\Support\Columns\Column;
use ACTTraining\QueryBuilder\TableBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/**
 * The search box must narrow a report, never widen it.
 *
 * rowsQuery() appended each searchable column as a bare top-level orWhere, after
 * everything query() returned, so the predicate became
 * `(report constraints) AND ... OR column LIKE '%term%'` and any report with real
 * constraints discarded them the moment someone typed (#3515 in ACT-Training/people).
 * Nothing inside query() could defend against it: the orWhere sat outside any group
 * the report created.
 */
class SearchScopingRecord extends Model
{
    protected $table = 'search_scoping_records';

    protected $guarded = [];

    public $timestamps = false;
}

class SearchScopingTable extends TableBuilder
{
    public function query(): Builder
    {
        return SearchScopingRecord::query()->where('category', 'health-and-safety');
    }

    public function columns(): array
    {
        return [
            Column::make('Title', 'title')->searchable(),
        ];
    }

    public function filters(): array
    {
        return [];
    }
}

beforeEach(function () {
    Schema::create('search_scoping_records', function ($table) {
        $table->id();
        $table->string('category');
        $table->string('title');
    });

    SearchScopingRecord::insert([
        ['category' => 'health-and-safety', 'title' => 'Fire Safety Awareness'],
        ['category' => 'health-and-safety', 'title' => 'Manual Handling'],
        ['category' => 'marketing', 'title' => 'Fire up your Facebook page'],
        ['category' => 'marketing', 'title' => 'Content strategy'],
    ]);
});

it('returns only the constrained rows when nothing is searched', function () {
    $table = new SearchScopingTable;

    expect($table->rowsQuery()->pluck('title')->all())
        ->toEqualCanonicalizing(['Fire Safety Awareness', 'Manual Handling']);
});

it('keeps the query constraints when a search term is typed', function () {
    $table = new SearchScopingTable;
    $table->searchBy = 'Fire';

    // Without grouping this returns the marketing row too, because the search ORs
    // past `category = health-and-safety`.
    expect($table->rowsQuery()->pluck('title')->all())
        ->toEqual(['Fire Safety Awareness']);
});

it('never returns more rows with a search term than without one', function () {
    $unsearched = (new SearchScopingTable)->rowsQuery()->count();

    $table = new SearchScopingTable;
    $table->searchBy = 'a';

    expect($table->rowsQuery()->count())->toBeLessThanOrEqual($unsearched);
});
