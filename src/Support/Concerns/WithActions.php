<?php

namespace ACTTraining\QueryBuilder\Support\Concerns;

use ACTTraining\QueryBuilder\Support\Actions\BaseAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Enumerable;

trait WithActions
{
    public function actions(): array
    {
        return [];
    }

    public function areActionsVisible(): bool
    {
        return count($this->actions()) > 0;
    }

    protected function resolveActions(): Enumerable
    {
        return collect($this->actions());
    }

    public function executeAction(string $key): mixed
    {
        $action = $this->resolveActions()->firstOrFail(fn (BaseAction $action): bool => $key === $action->key());

        if (! $action->isStandalone() && count($this->selectedRows) == 0) {
            return null;
        }

        $models = $action->isStandalone() ? collect() : $this->selectedModels($action);

        $response = $action->execute($models);

        if ($response !== false && ! $action->isStandalone()) {
            $this->clearSelection();
        }

        return $response;
    }

    /**
     * The selected rows, always within the table's search, filters and criteria.
     * With "Select all" that is every row they leave, on any page; otherwise the
     * ticked rows among them, so a stale selection can't reach rows the table no
     * longer shows.
     */
    protected function selectedRowsQuery(): Builder
    {
        $query = clone $this->rowsQuery; /* @phpstan-ignore-line */

        return $this->selectAll ? $query : $query->whereKey($this->selectedRows);
    }

    protected function selectedModels(BaseAction $action): Enumerable
    {
        $query = $this->selectedRowsQuery();

        if (! $action->isLazy()) {
            return $query->get();
        }

        // By key rather than by offset, so an action that changes or deletes rows
        // as it goes doesn't make the next chunk skip any.
        $model = $query->getModel();

        return $query->reorder()->lazyById($action->lazyChunkSize(), $model->getQualifiedKeyName(), $model->getKeyName());
    }
}
