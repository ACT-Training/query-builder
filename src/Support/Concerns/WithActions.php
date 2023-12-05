<?php

namespace ACTTraining\QueryBuilder\Support\Concerns;

use ACTTraining\QueryBuilder\Support\Actions\BaseAction;
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

        $models = collect();

        if (! $action->isStandalone() && (count($this->selectedRows) > 0 || $this->selectAll)) {
            if ($this->selectAll) {
                $models = $this->query()->get();
            } else {
                $models = $this->query()->whereIn($this->model()->getKeyName(), $this->selectedRows)->get();
            }
        }

        $response = $action->execute($models);

        if ($response !== false) {
            if (! $action->isStandalone()) {
                $this->clearSelection();
            }

        }

        return $response;
    }
}
