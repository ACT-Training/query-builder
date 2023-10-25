<?php

namespace ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Concerns;

trait WithMessage
{
    private string $message = 'No records found.';

    private string $information = 'Try creating some records.';

    public function message(): string
    {
        return $this->message;
    }

    public function information(): string
    {
        return $this->information;
    }

    public function setRecordsNotFound(string $message, $information = ''): static
    {
        $this->message = $message;
        $this->information = $information;

        return $this;
    }

}