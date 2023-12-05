<?php

declare(strict_types=1);

namespace ACTTraining\QueryBuilder\Support\Collection;

use ACTTraining\QueryBuilder\Support\Concerns\CollectionTrait;
use ACTTraining\QueryBuilder\Support\Contracts\CriteriaInterface;

/** @phpstan-consistent-constructor */
class CriteriaCollection implements CriteriaInterface
{
    use CollectionTrait;

    public static function oneOf(array $criteria): OneOfCriteriaCollection
    {
        return OneOfCriteriaCollection::create($criteria);
    }

    public static function allOf(array $criteria): AllOfCriteriaCollection
    {
        return AllOfCriteriaCollection::create($criteria);
    }

    public function apply($query): void
    {
        foreach ($this->criteria as $criteria) {
            $criteria->apply($query);
        }
    }
}
