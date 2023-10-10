<?php

declare(strict_types=1);

namespace ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Contracts;

use Illuminate\Database\Eloquent\Builder;

interface CriteriaInterface
{
    public function apply(Builder $query);
}
