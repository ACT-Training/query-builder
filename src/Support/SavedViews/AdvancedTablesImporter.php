<?php

namespace ACTTraining\QueryBuilder\Support\SavedViews;

use ACTTraining\QueryBuilder\Support\Contracts\SavedViewRepository;
use Closure;
use Illuminate\Support\Facades\DB;

/**
 * Converts Filament Advanced Tables saved views (the `filament_filter_sets` and
 * `filament_filter_set_user` tables) into saved views.
 *
 * Advanced Tables stores Filament's filter state, which only the app can
 * translate into its own filters or criteria, so the app supplies that mapping.
 * The importer carries across the owner, name, search, sort, sharing and
 * favourites. Running it again updates the views it imported before rather
 * than duplicating them.
 */
class AdvancedTablesImporter
{
    public const SOURCE_PREFIX = 'advanced-tables:';

    /**
     * Keys Advanced Tables can save that have no equivalent here. The importer
     * warns about them so someone can check the converted view.
     */
    protected const UNSUPPORTED_KEYS = [
        'activeSet' => 'it was based on a preset view',
        'tableGrouping' => 'it grouped rows',
        'tableColumnSearchQueries' => 'it searched individual columns',
    ];

    public function __construct(protected SavedViewRepository $repository) {}

    /**
     * Import straight from the Advanced Tables tables.
     *
     * @param  array<string, string>  $tables  Advanced Tables `resource` value => saved views table key
     * @param  Closure(array<string, mixed> $filters, array<string, mixed> $row): (array<string, mixed>|null)  $mapState
     */
    public function importFromDatabase(array $tables, Closure $mapState, ?string $connection = null): ImportReport
    {
        $database = DB::connection($connection);

        return $this->import(
            $database->table('filament_filter_sets')->whereIn('resource', array_keys($tables))->orderBy('id')->get(),
            $database->table('filament_filter_set_user')->orderBy('id')->get(),
            $tables,
            $mapState,
        );
    }

    /**
     * Import from rows already read, as arrays or objects with the Advanced Tables columns.
     *
     * `$mapState` receives the decoded `filters` column and the whole row. It
     * returns the parts of the saved view state it can work out (usually
     * `filters` for a TableBuilder, or `criteria`, `andOr` and `columns` for a
     * QueryBuilder), which replace the importer's own, or null to skip the row.
     *
     * @param  iterable<array<string, mixed>|object>  $filterSets
     * @param  iterable<array<string, mixed>|object>  $filterSetUsers
     * @param  array<string, string>  $tables  Advanced Tables `resource` value => saved views table key
     * @param  Closure(array<string, mixed> $filters, array<string, mixed> $row): (array<string, mixed>|null)  $mapState
     */
    public function import(iterable $filterSets, iterable $filterSetUsers, array $tables, Closure $mapState): ImportReport
    {
        $report = new ImportReport;

        /** @var array<string, int|string> $imported Advanced Tables id => saved view id */
        $imported = [];

        foreach ($filterSets as $row) {
            $row = (array) $row;
            $id = $row['id'];

            $tableKey = $tables[$row['resource']] ?? null;

            if ($tableKey === null) {
                $report->skipped[$id] = "No table key for resource {$row['resource']}.";

                continue;
            }

            $filters = $this->decodeFilters($row['filters'] ?? null);
            $mapped = $mapState($filters, $row);

            if ($mapped === null) {
                $report->skipped[$id] = "The app's mapping skipped it.";

                continue;
            }

            $state = array_replace($this->baseState($filters), $mapped);
            $shared = $this->isShared($row);
            $name = mb_substr(trim((string) $row['name']), 0, 255);

            $this->warnAboutUnsupportedKeys($report, $row, $filters);

            if (! empty($row['is_global_favorite'])) {
                $report->warnings[] = $this->describe($row).' was a favourite for everyone. It is now shared, but people need to favourite it themselves.';
            }

            $source = self::SOURCE_PREFIX.$id;
            $existing = $this->repository->findBySource($source);

            if ($existing) {
                $this->repository->rename($existing->id, $name);
                $this->repository->updateState($existing->id, $state);
                $this->repository->share($existing->id, $shared);
                $imported[(string) $id] = $existing->id;
                $report->updated++;

                continue;
            }

            $view = $this->repository->create($row['user_id'], $tableKey, $name, $state, $shared, $source);
            $imported[(string) $id] = $view->id;
            $report->created++;
        }

        foreach ($filterSetUsers as $pivot) {
            $pivot = (array) $pivot;
            $viewId = $imported[(string) $pivot['filter_set_id']] ?? null;

            // A hidden row means someone removed a global favourite from their list.
            if ($viewId === null || ! ($pivot['is_visible'] ?? true)) {
                continue;
            }

            $this->repository->favourite($viewId, $pivot['user_id'], true);
            $report->favourites++;
        }

        return $report;
    }

    /**
     * The search and sort, which Filament stores the same way for every table.
     */
    protected function baseState(array $filters): array
    {
        return [
            'version' => SavedView::STATE_VERSION,
            'search' => (string) ($filters['tableSearchQuery'] ?? ''),
            'sort' => [
                'by' => (string) ($filters['tableSortColumn'] ?? ''),
                'direction' => ($filters['tableSortDirection'] ?? 'asc') === 'desc' ? 'desc' : 'asc',
            ],
        ];
    }

    /**
     * Public views stay shared. A rejected view was never shown to others, so it stays private.
     */
    protected function isShared(array $row): bool
    {
        if (($row['status'] ?? 'approved') === 'rejected') {
            return false;
        }

        return ! empty($row['is_public']) || ! empty($row['is_global_favorite']);
    }

    protected function decodeFilters(mixed $filters): array
    {
        if (is_string($filters)) {
            $filters = json_decode($filters, true);
        }

        return is_array($filters) ? $filters : [];
    }

    protected function warnAboutUnsupportedKeys(ImportReport $report, array $row, array $filters): void
    {
        foreach (self::UNSUPPORTED_KEYS as $key => $reason) {
            if (! empty($filters[$key])) {
                $report->warnings[] = $this->describe($row)." may not match the original: {$reason}.";
            }
        }
    }

    protected function describe(array $row): string
    {
        return "View {$row['id']} (\"{$row['name']}\")";
    }
}
