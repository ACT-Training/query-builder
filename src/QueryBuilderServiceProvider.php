<?php

namespace ACTTraining\QueryBuilder;

use ACTTraining\QueryBuilder\Commands\QueryBuilderCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class QueryBuilderServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('query-builder')
            ->hasConfigFile()
            ->hasViews()
//            ->hasMigration('create_query-builder_table')
            ->hasCommand(QueryBuilderCommand::class);
    }
}
