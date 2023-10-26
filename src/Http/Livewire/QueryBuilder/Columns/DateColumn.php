<?php

namespace ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Columns;

class DateColumn extends BaseColumn
{
    public string $component = 'columns.date-column';

    protected string $format = 'd/m/Y';

    protected bool $showHumanDiff = false;

    public function format(string $format): static
    {
        $this->format = $format;

        return $this;
    }

    public function humanDiff(): static
    {
        $this->showHumanDiff = true;

        return $this;
    }

    public function dateFormat(): string
    {
        return $this->format;
    }

    public function showHumanDiff(): bool
    {
        return $this->showHumanDiff;
    }

}
