<?php

namespace ACTTraining\QueryBuilder\Commands;

use Illuminate\Console\Command;

class QueryBuilderCommand extends Command
{
    public $signature = 'query-builder';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
