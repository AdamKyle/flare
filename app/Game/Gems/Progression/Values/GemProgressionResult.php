<?php

namespace App\Game\Gems\Progression\Values;

/**
 * Immutable outcome of applying Gem progression XP to one global/personal
 * Gem progression row.
 */
class GemProgressionResult
{
    public function __construct(
        private readonly int $oldLevel,
        private readonly int $newLevel,
        private readonly int $oldXp,
        private readonly int $newXp,
        private readonly int $xpApplied,
        private readonly bool $leveledUp,
        private readonly bool $isMaxLevel,
    ) {}

    /**
     * The level before this XP was applied.
     */
    public function oldLevel(): int
    {
        return $this->oldLevel;
    }

    /**
     * The level after this XP was applied.
     */
    public function newLevel(): int
    {
        return $this->newLevel;
    }

    /**
     * The current-level XP before this XP was applied.
     */
    public function oldXp(): int
    {
        return $this->oldXp;
    }

    /**
     * The current-level XP after this XP was applied.
     */
    public function newXp(): int
    {
        return $this->newXp;
    }

    /**
     * The XP amount that was applied.
     */
    public function xpApplied(): int
    {
        return $this->xpApplied;
    }

    /**
     * Whether the level changed as a result of this XP.
     */
    public function leveledUp(): bool
    {
        return $this->leveledUp;
    }

    /**
     * Whether the progression is now at its max level.
     */
    public function isMaxLevel(): bool
    {
        return $this->isMaxLevel;
    }
}
