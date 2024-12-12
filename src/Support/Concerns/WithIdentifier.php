<?php

namespace ACTTraining\QueryBuilder\Support\Concerns;

trait WithIdentifier
{
    protected string $identifier = 'table-builder';

    public function identifier(): string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier = 'table-builder'): static
    {
        $this->identifier = $identifier;

        return $this;
    }
}
