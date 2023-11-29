<?php

namespace ACTTraining\QueryBuilder\Http\Livewire\QueryBuilder\Concerns;

trait WithMessage
{
    private string $message = 'No records found.';

    private string $information = 'Try creating some records.';

    private bool $enabled = true;

    public function message(): string
    {
        return $this->message;
    }

    public function information(): string
    {
        return $this->information;
    }

    public function messageEnabled(): bool
    {
        return $this->enabled;
    }

    public function setRecordsNotFound(string $message, $information = ''): static
    {
        $this->message = $message;
        $this->information = $information;

        return $this;
    }

    public function showMessage(bool $enabled = true): static
    {
        $this->enabled = $enabled;

        return $this;
    }
}
