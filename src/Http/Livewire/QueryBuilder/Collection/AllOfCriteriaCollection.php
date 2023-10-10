<?php

declare(strict_types=1);

namespace ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Collection;

use ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Concerns\CollectionTrait;
use ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Contracts\CriteriaInterface;

/** @phpstan-consistent-constructor */
class AllOfCriteriaCollection implements CriteriaInterface
{
    use CollectionTrait;

    public function apply($query): void
    {
        $query->where(function ($innerQuery) {
            foreach ($this->criteria as $criteria) {
                $criteria->apply($innerQuery);
            }
        });
    }
}
