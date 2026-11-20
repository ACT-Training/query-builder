<?php

namespace ACTTraining\QueryBuilder\Support\SavedViews;

/**
 * A saved view as one user sees it. `isFavourite` and `isDefault` belong to
 * that user, not to the view's owner.
 */
final readonly class SavedView
{
    /**
     * The format version stored in every saved state.
     */
    public const STATE_VERSION = 1;

    public function __construct(
        public int|string $id,
        public int|string $ownerId,
        public string $tableKey,
        public string $name,
        public array $state,
        public bool $isShared = false,
        public bool $isFavourite = false,
        public bool $isDefault = false,
    ) {}

    public function isOwnedBy(int|string|null $userId): bool
    {
        return $userId !== null && (string) $this->ownerId === (string) $userId;
    }
}
