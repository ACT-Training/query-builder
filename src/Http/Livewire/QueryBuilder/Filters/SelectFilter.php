<?php

namespace ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Filters;

class SelectFilter extends BaseFilter
{
    public $options = [];

    public string $component = 'select';

    public function options()
    {
        return $this->options;
    }

    public function withOptions(array $options = []): static
    {
        $this->options = $options;

        return $this;
    }
}
