<?php

namespace ACTTraining\QueryBuilder\Support;

class CardLayout
{
    protected ?string $imageKey = null;

    protected ?string $placeholderImage = null;

    protected int $gridColumns = 3;

    protected string $cardView = 'query-builder::card';

    public static function make(): self
    {
        return new self;
    }

    public function image(string $key): static
    {
        $this->imageKey = $key;

        return $this;
    }

    public function placeholder(string $url): static
    {
        $this->placeholderImage = $url;

        return $this;
    }

    public function columns(int $columns): static
    {
        $this->gridColumns = $columns;

        return $this;
    }

    public function view(string $view): static
    {
        $this->cardView = $view;

        return $this;
    }

    public function getImageKey(): ?string
    {
        return $this->imageKey;
    }

    public function getPlaceholderImage(): ?string
    {
        return $this->placeholderImage;
    }

    public function getGridColumns(): int
    {
        return $this->gridColumns;
    }

    public function getCardView(): string
    {
        return $this->cardView;
    }

    public function getImageUrl(mixed $row): ?string
    {
        if (! $this->imageKey) {
            return null;
        }

        return data_get($row, $this->imageKey) ?? $this->placeholderImage;
    }
}
