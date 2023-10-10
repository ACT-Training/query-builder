<?php

declare(strict_types=1);

namespace ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Concerns;

use ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Contracts\CriteriaInterface;

trait AppliesCriteria
{
    public function scopeApply($query, CriteriaInterface $criteria)
    {
        $criteria->apply($query);

        return $query;
    }
}
