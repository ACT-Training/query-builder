<?php

use ACTTraining\QueryBuilder\Models\SavedViewRecord;
use ACTTraining\QueryBuilder\Support\Contracts\SavedViewRepository;
use ACTTraining\QueryBuilder\Support\SavedViews\AdvancedTablesImporter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Converting Filament Advanced Tables saved views at switchover (pipeline2 build plan 8.1).
 * The rows match Pipeline's `filament_filter_sets` and `filament_filter_set_user`
 * tables and the keys Advanced Tables 3.7 writes into `filters`.
 */
const ENQUIRIES = 'App\\Filament\\Resources\\EnquiryResource';

function filterSet(array $overrides = []): array
{
    return array_merge([
        'id' => 1,
        'user_id' => 1,
        'tenant_id' => null,
        'name' => 'Hot leads',
        'resource' => ENQUIRIES,
        'filters' => json_encode([
            'tableFilters' => ['status' => ['value' => 'hot']],
            'tableSearchQuery' => 'smith',
            'tableSortColumn' => 'received_at',
            'tableSortDirection' => 'desc',
            'toggledTableColumns' => ['email' => false],
        ]),
        'indicators' => '[]',
        'icon' => null,
        'color' => null,
        'is_public' => 0,
        'is_global_favorite' => 0,
        'status' => 'approved',
        'sort_order' => 1,
    ], $overrides);
}

/**
 * The app's mapping: Filament's filter state into this table's filter values.
 */
function enquiryMapping(): Closure
{
    return fn (array $filters) => [
        'filters' => isset($filters['tableFilters']['status']['value'])
            ? ['Status:=' => $filters['tableFilters']['status']['value']]
            : [],
    ];
}

function importer(): AdvancedTablesImporter
{
    return app(AdvancedTablesImporter::class);
}

beforeEach(function () {
    (include __DIR__.'/../database/migrations/create_query_builder_saved_views_table.php.stub')->up();
});

it('converts a view with its owner, name, search, sort and the app\'s filter mapping', function () {
    $report = importer()->import([filterSet()], [], [ENQUIRIES => 'enquiries'], enquiryMapping());

    $view = SavedViewRecord::sole();

    expect($report->created)->toBe(1)
        ->and($view->user_id)->toBe(1)
        ->and($view->table_key)->toBe('enquiries')
        ->and($view->name)->toBe('Hot leads')
        ->and($view->is_shared)->toBeFalse()
        ->and($view->source)->toBe('advanced-tables:1')
        ->and($view->state)->toBe([
            'version' => 1,
            'search' => 'smith',
            'sort' => ['by' => 'received_at', 'direction' => 'desc'],
            'filters' => ['Status:=' => 'hot'],
        ]);
});

it('keeps public views shared, but not rejected ones', function () {
    importer()->import([
        filterSet(['id' => 1, 'is_public' => 1]),
        filterSet(['id' => 2, 'is_public' => 1, 'status' => 'pending']),
        filterSet(['id' => 3, 'is_public' => 1, 'status' => 'rejected']),
        filterSet(['id' => 4, 'is_public' => 0]),
    ], [], [ENQUIRIES => 'enquiries'], enquiryMapping());

    expect(SavedViewRecord::orderBy('id')->pluck('is_shared', 'source')->all())->toBe([
        'advanced-tables:1' => true,
        'advanced-tables:2' => true,
        'advanced-tables:3' => false,
        'advanced-tables:4' => false,
    ]);
});

it('shares global favourites and warns that people must favourite them again', function () {
    $report = importer()->import([filterSet(['is_global_favorite' => 1])], [], [ENQUIRIES => 'enquiries'], enquiryMapping());

    expect(SavedViewRecord::sole()->is_shared)->toBeTrue()
        ->and($report->warnings)->toHaveCount(1)
        ->and($report->warnings[0])->toContain('favourite for everyone');
});

it('carries favourites across, including other people\'s shared views', function () {
    $report = importer()->import(
        [filterSet(['id' => 1, 'user_id' => 1, 'is_public' => 1])],
        [
            ['id' => 1, 'user_id' => 1, 'filter_set_id' => 1, 'sort_order' => 1, 'is_visible' => 1],
            ['id' => 2, 'user_id' => 2, 'filter_set_id' => 1, 'sort_order' => 1, 'is_visible' => 1],
            ['id' => 3, 'user_id' => 3, 'filter_set_id' => 1, 'sort_order' => 1, 'is_visible' => 0],
            ['id' => 4, 'user_id' => 2, 'filter_set_id' => 99, 'sort_order' => 1, 'is_visible' => 1],
        ],
        [ENQUIRIES => 'enquiries'],
        enquiryMapping(),
    );

    $repository = app(SavedViewRepository::class);
    $id = SavedViewRecord::sole()->id;

    expect($report->favourites)->toBe(2)
        ->and($repository->find($id, 1)->isFavourite)->toBeTrue()
        ->and($repository->find($id, 2)->isFavourite)->toBeTrue()
        ->and($repository->find($id, 3)->isFavourite)->toBeFalse();
});

it('skips views for tables it was not given, or that the mapping skips', function () {
    $report = importer()->import([
        filterSet(['id' => 1, 'resource' => 'App\\Filament\\Resources\\UserResource']),
        filterSet(['id' => 2, 'name' => 'Skip me']),
        filterSet(['id' => 3]),
    ], [], [ENQUIRIES => 'enquiries'], fn (array $filters, array $row) => $row['name'] === 'Skip me' ? null : []);

    expect($report->created)->toBe(1)
        ->and(array_keys($report->skipped))->toBe([1, 2]);
});

it('warns about views that relied on things saved views cannot do', function () {
    $report = importer()->import([filterSet([
        'filters' => json_encode(['activeSet' => 'lead', 'tableGrouping' => 'pipeline_stage.name']),
    ])], [], [ENQUIRIES => 'enquiries'], enquiryMapping());

    expect($report->created)->toBe(1)
        ->and($report->warnings)->toHaveCount(2);
});

it('can be run again without duplicating views', function () {
    $tables = [ENQUIRIES => 'enquiries'];
    $favourite = [['id' => 1, 'user_id' => 2, 'filter_set_id' => 1, 'sort_order' => 1, 'is_visible' => 1]];

    importer()->import([filterSet(['is_public' => 1])], $favourite, $tables, enquiryMapping());
    $report = importer()->import([filterSet(['name' => 'Renamed', 'is_public' => 0])], $favourite, $tables, enquiryMapping());

    expect($report->created)->toBe(0)
        ->and($report->updated)->toBe(1)
        ->and($report->favourites)->toBe(0)
        ->and(SavedViewRecord::sole())
        ->name->toBe('Renamed')
        ->is_shared->toBeFalse();
});

it('reads straight from the Advanced Tables tables', function () {
    Schema::create('filament_filter_sets', function ($table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->integer('tenant_id')->nullable();
        $table->string('name');
        $table->string('resource');
        $table->text('filters');
        $table->json('indicators');
        $table->string('icon')->nullable();
        $table->string('color')->nullable();
        $table->boolean('is_public');
        $table->boolean('is_global_favorite');
        $table->string('status')->default('approved');
        $table->smallInteger('sort_order')->default(1);
        $table->timestamps();
    });

    Schema::create('filament_filter_set_user', function ($table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->unsignedBigInteger('filter_set_id');
        $table->smallInteger('sort_order')->default(1);
        $table->boolean('is_visible')->default(true);
    });

    DB::table('filament_filter_sets')->insert([
        filterSet(['id' => 1, 'is_public' => 1]),
        filterSet(['id' => 2, 'resource' => 'App\\Filament\\Resources\\UserResource']),
    ]);
    DB::table('filament_filter_set_user')->insert(['user_id' => 2, 'filter_set_id' => 1, 'sort_order' => 1, 'is_visible' => 1]);

    $report = importer()->importFromDatabase([ENQUIRIES => 'enquiries'], enquiryMapping());

    $view = SavedViewRecord::sole();

    expect($report->created)->toBe(1)
        ->and($report->favourites)->toBe(1)
        ->and($view->is_shared)->toBeTrue()
        ->and($view->state['filters'])->toBe(['Status:=' => 'hot']);
});
