<?php

declare(strict_types=1);

namespace ACTTraining\QueryBuilder\Support\Concerns;

use ACTTraining\QueryBuilder\Support\Contracts\CriteriaInterface;

trait CollectionTrait
{
    private array $criteria = [];

    private function __construct(CriteriaInterface ...$criteria)
    {
        $this->criteria = $criteria;
    }

    public static function create(array $criteria): self
    {
        return new static(...$criteria);
    }
}
