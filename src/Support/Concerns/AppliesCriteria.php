<?php

declare(strict_types=1);

namespace ACTTraining\QueryBuilder\Support\Concerns;

use ACTTraining\QueryBuilder\Support\Contracts\CriteriaInterface;

trait AppliesCriteria
{
    public function scopeApply($query, CriteriaInterface $criteria)
    {
        $criteria->apply($query);

        return $query;
    }
}
