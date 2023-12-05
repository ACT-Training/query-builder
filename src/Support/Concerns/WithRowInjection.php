<?php

namespace ACTTraining\QueryBuilder\Support\Concerns;

trait WithRowInjection
{
    public function rowPreview($row): bool
    {
        return false;
    }

    public function injectRow($row): string
    {
        return '';
    }
}
