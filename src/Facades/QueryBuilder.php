<?php

namespace ACTTraining\QueryBuilder\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \ACTTraining\QueryBuilder\QueryBuilder
 */
class QueryBuilder extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \ACTTraining\QueryBuilder\QueryBuilder::class;
    }
}
