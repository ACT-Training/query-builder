<?php

namespace ACTTraining\QueryBuilder\Support\SavedViews;

use ACTTraining\QueryBuilder\Models\SavedViewPreference;
use ACTTraining\QueryBuilder\Models\SavedViewRecord;
use ACTTraining\QueryBuilder\Support\Contracts\SavedViewRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Stores saved views in the package's own tables (see the
 * `create_query_builder_saved_views_table` migration).
 */
class EloquentSavedViewRepository implements SavedViewRepository
{
    public function forUser(int|string $userId, string $tableKey): array
    {
        return $this->visibleTo($userId)
            ->where('table_key', $tableKey)
            ->orderBy('name')
            ->get()
            ->map(fn (SavedViewRecord $record) => $this->toSavedView($record, $userId))
            ->all();
    }

    public function find(int|string $id, int|string $userId): ?SavedView
    {
        $record = $this->visibleTo($userId)->find($id);

        return $record ? $this->toSavedView($record, $userId) : null;
    }

    public function defaultFor(int|string $userId, string $tableKey): ?SavedView
    {
        $record = $this->visibleTo($userId)
            ->where('table_key', $tableKey)
            ->whereHas('preferences', fn (Builder $query) => $query->where('user_id', $userId)->where('is_default', true))
            ->first();

        return $record ? $this->toSavedView($record, $userId) : null;
    }

    public function findBySource(string $source): ?SavedView
    {
        $record = SavedViewRecord::query()->where('source', $source)->first();

        return $record ? $this->toSavedView($record, $record->user_id) : null;
    }

    public function create(
        int|string $userId,
        string $tableKey,
        string $name,
        array $state,
        bool $shared = false,
        ?string $source = null,
    ): SavedView {
        $record = SavedViewRecord::query()->create([
            'user_id' => $userId,
            'table_key' => $tableKey,
            'name' => $name,
            'state' => $state,
            'is_shared' => $shared,
            'source' => $source,
        ]);

        return $this->toSavedView($record, $userId);
    }

    public function rename(int|string $id, string $name): void
    {
        SavedViewRecord::query()->whereKey($id)->update(['name' => $name]);
    }

    public function updateState(int|string $id, array $state): void
    {
        SavedViewRecord::query()->findOrFail($id)->update(['state' => $state]);
    }

    public function share(int|string $id, bool $shared): void
    {
        SavedViewRecord::query()->whereKey($id)->update(['is_shared' => $shared]);

        // A view that is no longer shared can't stay in other people's lists.
        if (! $shared) {
            $ownerId = SavedViewRecord::query()->whereKey($id)->value('user_id');

            SavedViewPreference::query()
                ->where('saved_view_id', $id)
                ->where('user_id', '!=', $ownerId)
                ->delete();
        }
    }

    public function delete(int|string $id): void
    {
        DB::transaction(function () use ($id) {
            SavedViewPreference::query()->where('saved_view_id', $id)->delete();
            SavedViewRecord::query()->whereKey($id)->delete();
        });
    }

    public function favourite(int|string $id, int|string $userId, bool $favourite): void
    {
        SavedViewPreference::query()->updateOrCreate(
            ['saved_view_id' => $id, 'user_id' => $userId],
            ['is_favourite' => $favourite],
        );
    }

    public function setDefault(int|string $userId, string $tableKey, int|string|null $id): void
    {
        DB::transaction(function () use ($userId, $tableKey, $id) {
            SavedViewPreference::query()
                ->where('user_id', $userId)
                ->where('is_default', true)
                ->whereIn('saved_view_id', SavedViewRecord::query()->select('id')->where('table_key', $tableKey))
                ->update(['is_default' => false]);

            if ($id !== null) {
                SavedViewPreference::query()->updateOrCreate(
                    ['saved_view_id' => $id, 'user_id' => $userId],
                    ['is_default' => true],
                );
            }
        });
    }

    /**
     * @return Builder<SavedViewRecord>
     */
    protected function visibleTo(int|string $userId): Builder
    {
        return SavedViewRecord::query()
            ->where(fn (Builder $query) => $query->where('user_id', $userId)->orWhere('is_shared', true))
            ->with(['preferences' => fn ($query) => $query->where('user_id', $userId)]);
    }

    protected function toSavedView(SavedViewRecord $record, int|string $userId): SavedView
    {
        /** @var SavedViewPreference|null $preference */
        $preference = $record->relationLoaded('preferences')
            ? $record->preferences->first()
            : $record->preferences()->where('user_id', $userId)->first();

        return new SavedView(
            id: $record->getKey(),
            ownerId: $record->user_id,
            tableKey: $record->table_key,
            name: $record->name,
            state: $record->state ?? [],
            isShared: $record->is_shared,
            isFavourite: (bool) $preference?->is_favourite,
            isDefault: (bool) $preference?->is_default,
        );
    }
}
