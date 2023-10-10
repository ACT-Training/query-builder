<?php

declare(strict_types=1);

namespace ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Concerns;

use ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Contracts\CriteriaInterface;

trait CollectionTrait
{
    private $criteria = [];

    private function __construct(CriteriaInterface ...$criteria)
    {
        $this->criteria = $criteria;
    }

    public static function create(array $criteria): self
    {
        return new static(...$criteria);
    }
}
