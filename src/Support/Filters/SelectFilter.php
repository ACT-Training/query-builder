<?php

namespace ACTTraining\QueryBuilder\Support\Filters;

class SelectFilter extends BaseFilter
{
    public $options = [];

    public $optionsInGroups = [];

    public string $component = 'select';

    private string $prompt = 'Select an option';

    public function options()
    {
        return $this->options;
    }

    public function optionsInGroups()
    {
        return $this->optionsInGroups;
    }

    public function prompt(): string
    {
        return $this->prompt;
    }

    public function withOptions(array $options = []): static
    {
        $this->options = $options;

        return $this;
    }

    public function withOptionGroups(array $optionsInGroups = []): static
    {
        $this->optionsInGroups = $optionsInGroups;

        return $this;
    }

    public function setPrompt(string $prompt): static
    {
        $this->prompt = $prompt;

        return $this;
    }
}
