<?php

declare(strict_types=1);

namespace ACTTraining\QueryBuilder\Support\Collection;

use ACTTraining\QueryBuilder\Support\Concerns\CollectionTrait;
use ACTTraining\QueryBuilder\Support\Contracts\CriteriaInterface;

/** @phpstan-consistent-constructor */
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
