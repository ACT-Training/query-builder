<?php

namespace ACTTraining\QueryBuilder\Support\Concerns;

trait WithViews
{

    /**
     * The view to add markup above the table.
     */
    public function headerView(): ?string
    {
        return 'query-builder::header';
    }

    /**
     * The view to add markup below the table.
     */
    public function footerView(): ?string
    {
        return 'query-builder::footer';
    }

    /**
     * The view to display when there are no results.
     */
    public function emptyView(): ?string
    {
        return 'query-builder::none-found';
    }

}
