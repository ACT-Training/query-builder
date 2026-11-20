<?php

use ACTTraining\QueryBuilder\Support\SavedViews\EloquentSavedViewRepository;

return [
    /*
     * Should the query builder use logical groups?
     * This feature is not implemented yet.
     */
    'enable_groups' => false,

    /*
     * Saved table views. Publish and run the package's migration to use the
     * built-in storage, or bind your own class implementing
     * ACTTraining\QueryBuilder\Support\Contracts\SavedViewRepository here.
     */
    'saved_views' => [
        'repository' => EloquentSavedViewRepository::class,
    ],
];
