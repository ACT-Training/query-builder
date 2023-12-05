<?php

declare(strict_types=1);

namespace ACTTraining\QueryBuilder\Support\Contracts;

use Illuminate\Database\Eloquent\Builder;

interface CriteriaInterface
{
    public function apply(Builder $query);
}
