<?php

namespace ACTTraining\QueryBuilder\Support\Concerns;

use Illuminate\Support\Str;
use Livewire\Exceptions\NonPublicComponentMethodCall;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;

/**
 * Livewire lets the browser call any public method and update any public property.
 * The methods that set a table up, and the settings that decide which query a
 * report runs, are for the table's own code only.
 */
trait GuardsBrowserCalls
{
    /**
     * Public methods that the browser can't call. Override and merge to add more.
     * When you add a public set-up method to the package, add it here too.
     */
    protected function methodsNotCallableFromBrowser(): array
    {
        return [
            'additionalSearchables',
            'applyPagination',
            'displayColumnSelector',
            'displayFilters',
            'displayRowSelector',
            'displaySearch',
            'displaySearchableIcon',
            'displayToolbar',
            'enableQueryBuilder',
            'enableSavedViews',
            'loadingIndicator',
            'pageName',
            'rowClickable',
            'searchable',
            'setIdentifier',
            'setRecordsNotFound',
            'setSort',
            'setSpinnerColor',
            'shouldScrollTo',
            'showMessage',
            'sortable',
            'useCachedRows',
            'usePagination',
            // Livewire 3 doesn't stop the browser calling these lifecycle hooks.
            'bootedWithSorting',
            'callGuardsBrowserCalls',
            'callWithSorting',
            'initializeWithSorting',
        ];
    }

    /**
     * Public properties that the browser can't change. Override and merge to add more.
     */
    protected function propertiesNotUpdatableFromBrowser(): array
    {
        return ['enableGroupBy', 'enableQueryBuilder'];
    }

    /**
     * Livewire calls this before it runs a method that the browser asked for.
     */
    public function callGuardsBrowserCalls($methodName, $params = [], $returnEarly = null, $metadata = null): void
    {
        if (in_array($methodName, $this->methodsNotCallableFromBrowser(), true)) {
            throw new NonPublicComponentMethodCall($methodName);
        }
    }

    /**
     * Livewire calls this before it applies a property change from the browser.
     */
    public function updatingGuardsBrowserCalls($property, $value = null): void
    {
        $name = Str::before((string) $property, '.');

        if (in_array($name, $this->propertiesNotUpdatableFromBrowser(), true)) {
            throw new CannotUpdateLockedPropertyException($name);
        }
    }
}
