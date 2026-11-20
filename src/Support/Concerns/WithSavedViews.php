<?php

namespace ACTTraining\QueryBuilder\Support\Concerns;

use ACTTraining\QueryBuilder\Support\Contracts\SavedViewRepository;
use ACTTraining\QueryBuilder\Support\SavedViews\SavedView;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Lets people save the table's current filters, search, sort, columns and rows
 * per page as a named view, share it, favourite it and pick a default.
 *
 * Off unless the table calls `enableSavedViews('a-table-key')` in `config()`.
 */
trait WithSavedViews
{
    protected ?string $savedViewsKey = null;

    public int|string|null $activeSavedViewId = null;

    public bool $savedViewsBooted = false;

    public string $savedViewName = '';

    public bool $savedViewShared = false;

    /** @var array<int, SavedView>|null */
    protected ?array $savedViewsCache = null;

    /**
     * Turn saved views on. The key identifies this table in storage, so give
     * every table its own and don't change it once people have saved views.
     */
    public function enableSavedViews(string $tableKey): static
    {
        $this->savedViewsKey = $tableKey;

        return $this;
    }

    public function hasSavedViews(): bool
    {
        return $this->savedViewsKey !== null && $this->savedViewsUserId() !== null;
    }

    public function savedViewsKey(): ?string
    {
        return $this->savedViewsKey;
    }

    /**
     * Runs after the component's own `booted()`, so `config()` has already run.
     * On the first request only, apply the user's default view unless the URL
     * already carries a search or filters.
     */
    public function bootedWithSavedViews(): void
    {
        if ($this->savedViewsBooted) {
            return;
        }

        $this->savedViewsBooted = true;

        if (! $this->hasSavedViews() || $this->requestHasTableState()) {
            return;
        }

        $default = $this->savedViewRepository()->defaultFor($this->savedViewsUserId(), $this->savedViewsKey);

        if ($default) {
            $this->applySavedViewState($default->state);
            $this->activeSavedViewId = $default->id;
        }
    }

    /**
     * @return array<int, SavedView>
     */
    public function savedViews(): array
    {
        if (! $this->hasSavedViews()) {
            return [];
        }

        return $this->savedViewsCache ??= $this->savedViewRepository()->forUser($this->savedViewsUserId(), $this->savedViewsKey);
    }

    /**
     * @return array<int, SavedView>
     */
    public function favouriteSavedViews(): array
    {
        return array_values(array_filter($this->savedViews(), fn (SavedView $view) => $view->isFavourite));
    }

    public function activeSavedView(): ?SavedView
    {
        foreach ($this->savedViews() as $view) {
            if ($this->isActiveSavedView($view)) {
                return $view;
            }
        }

        return null;
    }

    public function ownsSavedView(SavedView $view): bool
    {
        return $view->isOwnedBy($this->savedViewsUserId());
    }

    public function applySavedView(int|string $id): void
    {
        $view = $this->visibleSavedView($id);

        $this->applySavedViewState($view->state);
        $this->activeSavedViewId = $view->id;
    }

    public function saveView(): void
    {
        $this->ensureSavedViewsEnabled();

        $name = $this->cleanSavedViewName($this->savedViewName);

        if ($name === '') {
            $this->addError('savedViewName', 'Give the view a name.');

            return;
        }

        $view = $this->savedViewRepository()->create(
            $this->savedViewsUserId(),
            $this->savedViewsKey,
            $name,
            $this->currentSavedViewState(),
            $this->savedViewShared,
        );

        $this->activeSavedViewId = $view->id;
        $this->reset('savedViewName', 'savedViewShared');
        $this->savedViewsChanged();
        $this->dispatch('modal-close', name: $this->savedViewsModalName('save'));
    }

    /**
     * Replace a view's saved state with what the table shows now.
     */
    public function updateSavedView(int|string $id): void
    {
        $view = $this->ownedSavedView($id);

        $this->savedViewRepository()->updateState($view->id, $this->currentSavedViewState());
        $this->activeSavedViewId = $view->id;
        $this->savedViewsChanged();
    }

    public function renameSavedView(int|string $id, string $name): void
    {
        $view = $this->ownedSavedView($id);
        $name = $this->cleanSavedViewName($name);

        if ($name === '') {
            return;
        }

        $this->savedViewRepository()->rename($view->id, $name);
        $this->savedViewsChanged();
    }

    public function deleteSavedView(int|string $id): void
    {
        $view = $this->ownedSavedView($id);

        $this->savedViewRepository()->delete($view->id);

        if ($this->isActiveSavedView($view)) {
            $this->activeSavedViewId = null;
        }

        $this->savedViewsChanged();
    }

    public function toggleSavedViewShared(int|string $id): void
    {
        $view = $this->ownedSavedView($id);

        $this->savedViewRepository()->share($view->id, ! $view->isShared);
        $this->savedViewsChanged();
    }

    public function toggleSavedViewFavourite(int|string $id): void
    {
        $view = $this->visibleSavedView($id);

        $this->savedViewRepository()->favourite($view->id, $this->savedViewsUserId(), ! $view->isFavourite);
        $this->savedViewsChanged();
    }

    /**
     * Make the view the user's default for this table, or clear it if it already is.
     */
    public function toggleDefaultSavedView(int|string $id): void
    {
        $view = $this->visibleSavedView($id);

        $this->savedViewRepository()->setDefault(
            $this->savedViewsUserId(),
            $this->savedViewsKey,
            $view->isDefault ? null : $view->id,
        );
        $this->savedViewsChanged();
    }

    /**
     * The table's current state, in the form a saved view stores it.
     */
    public function currentSavedViewState(): array
    {
        return array_merge([
            'version' => SavedView::STATE_VERSION,
            'search' => $this->searchBy,
            'sort' => ['by' => $this->sortBy, 'direction' => $this->sortDirection],
            'perPage' => $this->perPage,
        ], $this->savedViewFilterState());
    }

    /**
     * Put the table into a saved state. Anything the table no longer has (a
     * removed filter, column or condition) is dropped rather than applied.
     */
    public function applySavedViewState(array $state): void
    {
        $this->searchBy = is_string($state['search'] ?? null) ? $state['search'] : '';

        $sortBy = $state['sort']['by'] ?? '';
        $sortable = $this->resolveColumns()->filter(fn ($column) => $column->isSortable())->pluck('key')->all();

        if (is_string($sortBy) && in_array($sortBy, $sortable, true)) {
            $this->sortBy = $sortBy;
            $this->sortDirection = ($state['sort']['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = '';
            $this->sortDirection = 'asc';
        }

        $perPage = $state['perPage'] ?? null;

        if (in_array($perPage, $this->rowOptions, false)) {
            $this->perPage = (int) $perPage;
        }

        $this->applySavedViewFilterState($state);
        $this->resetPage();
    }

    /**
     * The filters part of the saved state. TableBuilder saves its filter values;
     * QueryBuilder overrides this to save criteria and columns.
     */
    protected function savedViewFilterState(): array
    {
        return ['filters' => array_filter($this->filterValues, fn ($value) => $value !== null && $value !== '')];
    }

    protected function applySavedViewFilterState(array $state): void
    {
        $saved = is_array($state['filters'] ?? null) ? $state['filters'] : [];
        $values = [];

        foreach ($this->filters() as $filter) {
            $values[$filter->code()] = $saved[$filter->code()] ?? null;
        }

        $this->filterValues = $values;
    }

    protected function isActiveSavedView(SavedView $view): bool
    {
        return (string) $view->id === (string) $this->activeSavedViewId;
    }

    /**
     * Trim the name and cut it to fit the column.
     */
    protected function cleanSavedViewName(string $name): string
    {
        return mb_substr(trim($name), 0, 255);
    }

    protected function savedViewsModalName(string $modal): string
    {
        return $this->identifier().'-'.$modal.'-view';
    }

    protected function savedViewRepository(): SavedViewRepository
    {
        return app(SavedViewRepository::class);
    }

    protected function savedViewsUserId(): int|string|null
    {
        return auth()->id();
    }

    protected function savedViewsChanged(): void
    {
        $this->savedViewsCache = null;
    }

    /**
     * True when the page was opened with a search or filters in its URL.
     */
    protected function requestHasTableState(): bool
    {
        $query = request()->query();

        return filled($query['s'] ?? null) || filled($query['f'] ?? null);
    }

    protected function ensureSavedViewsEnabled(): void
    {
        if (! $this->hasSavedViews()) {
            throw new AuthorizationException('Saved views are not available.');
        }
    }

    protected function visibleSavedView(int|string $id): SavedView
    {
        $this->ensureSavedViewsEnabled();

        $view = $this->savedViewRepository()->find($id, $this->savedViewsUserId());

        if (! $view || $view->tableKey !== $this->savedViewsKey) {
            throw new AuthorizationException('That view is not available.');
        }

        return $view;
    }

    protected function ownedSavedView(int|string $id): SavedView
    {
        $view = $this->visibleSavedView($id);

        if (! $this->ownsSavedView($view)) {
            throw new AuthorizationException('Only the person who saved a view can change it.');
        }

        return $view;
    }
}
