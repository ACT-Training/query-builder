<?php

namespace ACTTraining\QueryBuilder\Support\Contracts;

use ACTTraining\QueryBuilder\Support\SavedViews\SavedView;

/**
 * Where saved table views are stored. The package binds an Eloquent
 * implementation that uses its own migration; an app can bind its own
 * implementation in `query-builder.saved_views.repository` to use another table.
 *
 * Every read is from one user's point of view: the views they own plus the
 * views others have shared, with that user's favourite and default flags.
 * Permission checks (only the owner renames, shares or deletes) happen in the
 * table component, not here.
 */
interface SavedViewRepository
{
    /**
     * The user's own views and every shared view for the table, ordered by name.
     *
     * @return array<int, SavedView>
     */
    public function forUser(int|string $userId, string $tableKey): array;

    /**
     * A view the user can see (their own or a shared one), or null.
     */
    public function find(int|string $id, int|string $userId): ?SavedView;

    /**
     * The view the user chose as their default for the table, if they can still see it.
     */
    public function defaultFor(int|string $userId, string $tableKey): ?SavedView;

    /**
     * The view imported from the given source (for example `advanced-tables:12`), seen by its owner.
     */
    public function findBySource(string $source): ?SavedView;

    public function create(
        int|string $userId,
        string $tableKey,
        string $name,
        array $state,
        bool $shared = false,
        ?string $source = null,
    ): SavedView;

    public function rename(int|string $id, string $name): void;

    public function updateState(int|string $id, array $state): void;

    public function share(int|string $id, bool $shared): void;

    public function delete(int|string $id): void;

    public function favourite(int|string $id, int|string $userId, bool $favourite): void;

    /**
     * Make the view the user's default for its table, clearing any other default
     * they had for that table. Pass null to clear the default.
     */
    public function setDefault(int|string $userId, string $tableKey, int|string|null $id): void;
}
