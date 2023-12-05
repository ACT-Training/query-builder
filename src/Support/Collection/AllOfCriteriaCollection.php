<?php

declare(strict_types=1);

namespace ACTTraining\QueryBuilder\Support\Collection;

use ACTTraining\QueryBuilder\Support\Concerns\CollectionTrait;
use ACTTraining\QueryBuilder\Support\Contracts\CriteriaInterface;

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
