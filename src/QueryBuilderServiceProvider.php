<?php

namespace ACTTraining\QueryBuilder;

use ACTTraining\QueryBuilder\Commands\QueryBuilderCommand;
use Illuminate\Support\Facades\Blade;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class QueryBuilderServiceProvider extends PackageServiceProvider
{

    public function bootingPackage(): void
    {
        Blade::component('query-builder::components.columns.boolean-column', 'columns.boolean-column');
        Blade::component('query-builder::components.columns.column', 'columns.column');
        Blade::component('query-builder::components.columns.date-column', 'columns.date-column');
        Blade::component('query-builder::components.columns.view-column', 'columns.view-column');
    }


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
            ->hasCommand(QueryBuilderCommand::class);
    }
}
