<?php

/** @noinspection PhpUndefinedMethodInspection */

namespace ACTTraining\QueryBuilder;

use ACTTraining\QueryBuilder\Support\Concerns\WithColumnsBuilder;

abstract class ReportBuilder extends TableBuilder
{
    use WithColumnsBuilder;

}
