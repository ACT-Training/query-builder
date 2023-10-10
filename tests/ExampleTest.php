<?php

use ACTTraining\QueryBuilder\Commands\QueryBuilderCommand;
use function Pest\Laravel\artisan;

it('can test', function () {
    artisan(QueryBuilderCommand::class)
        ->assertExitCode(0);
});
