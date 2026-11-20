<?php

namespace ACTTraining\QueryBuilder\Support\SavedViews;

/**
 * What an import did, so the app's command can print it and someone can check it.
 */
class ImportReport
{
    public int $created = 0;

    public int $updated = 0;

    public int $favourites = 0;

    /** @var array<int|string, string> source row id => why it was skipped */
    public array $skipped = [];

    /** @var array<int, string> */
    public array $warnings = [];

    public function imported(): int
    {
        return $this->created + $this->updated;
    }
}
