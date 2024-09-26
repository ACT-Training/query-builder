<?php

/** @noinspection PhpUndefinedMethodInspection */

namespace ACTTraining\QueryBuilder;

use ACTTraining\QueryBuilder\Support\Concerns\WithReportBuilder;

abstract class ReportBuilder extends QueryBuilder
{
    use WithReportBuilder;

}
