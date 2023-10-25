<?php

namespace ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Concerns;

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