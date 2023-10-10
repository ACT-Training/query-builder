<?php

declare(strict_types=1);

namespace ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Collection;

use ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Concerns\CollectionTrait;
use ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Contracts\CriteriaInterface;

class OneOfCriteriaCollection implements CriteriaInterface
{
    use CollectionTrait;

    public function apply($query): void
    {
        $query->where(function ($innerQuery) {
            foreach ($this->criteria as $criteria) {
                $innerQuery->orWhere(function ($anotherInnerQuery) use ($criteria) {
                    $criteria->apply($anotherInnerQuery);
                });
            }
        });
    }
}
