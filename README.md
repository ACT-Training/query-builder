# A drop in query builder for Laravel models.

Add a query builder and report table for youir models.

## Installation

You can install the package via composer:

```bash
composer require act-training/query-builder
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="query-builder-views"
```

## Usage
Models should use the AppliesCriteria trait.

```php
class User extends Authenticatable
{
    use AppliesCriteria;

    ...

}
```

Create a Livewire component and make sure it extends QueryBuilder.

```php
<?php

namespace App\Http\Livewire;

use ACTTraining\QueryBuilder\QueryBuilder;use ACTTraining\QueryBuilder\Support\Columns\BooleanColumn;use ACTTraining\QueryBuilder\Support\Columns\Column;use ACTTraining\QueryBuilder\Support\Columns\DateColumn;use ACTTraining\QueryBuilder\Support\Conditions\BooleanCondition;use ACTTraining\QueryBuilder\Support\Conditions\DateCondition;use ACTTraining\QueryBuilder\Support\Conditions\TextCondition;use App\Models\Employee;use Illuminate\Database\Eloquent\Builder;

class EmployeesReport extends QueryBuilder
{
    public function config(): void
    {
        $this
            ->enableQueryBuilder()
            ->rowClickable(false);
    }

    public function columns(): array
    {
        return [
            Column::make('Name', 'full_name')
                ->component('columns.common.title')
                ->sortable(),
            Column::make('Job Title', 'contract.job.name'),
            Column::make('Location', 'contract.location.name'),
            BooleanColumn::make('Line Manager', 'contract.line_manager')
                ->hideIf(false)
                ->justify('center'),
            DateColumn::make('Start Date', 'contract.start_date')
                ->humanDiff()
                ->justify('right'),
        ];
    }

    public function conditions(): array
    {
        return [
            TextCondition::make('Name', 'full_name'),
            TextCondition::make('Job Title', 'contract.job.name'),
            BooleanCondition::make('Line Manager', 'contract.line_manager'),
            TextCondition::make('Location', 'contract.location.name'),
            DateCondition::make('Start Date', 'contract.start_date'),
        ];
    }

    public function query(): Builder
    {
        return Employee::query()->with(['contract', 'contract.job', 'contract.location']);
    }

    public function rowClick($row): void
    {
        $this->dispatchBrowserEvent('notify', ['content' => 'The row was clicked', 'type' => 'success']);
    }

}
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Simon Barrett](https://github.com/ACT-Training)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
