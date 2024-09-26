<?php

namespace ACTTraining\QueryBuilder\Support\Concerns;

trait WithColumnsBuilder
{
    public function configuredColumns(): array
    {
        return [];
    }

    public function buildColumns(): array
    {
        return [];
    }

}
