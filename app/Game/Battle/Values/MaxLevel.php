<?php

namespace App\Game\Battle\Values;

class MaxLevel
{
    const MAX_LEVEL = 1000;

    const HALF = 500;

    const THREE_QUARTERS = 750;

    const LAST_LEG = 900;

    const HALF_PERCENT = 0.75;

    const THREE_QUARTERS_PERCENT = 0.50;

    const LAST_LEG_PERCENT = 0.25;

    /**
     * @var int
     */
    private $currentLevel = 0;

    /**
     * @var int
     */
    private $xp = 0;

    /**
     * MaxLevel constructor.
     */
    public function __construct(int $currentLevel, int $xp)
    {
        $this->currentLevel = $currentLevel;
        $this->xp = $xp;
    }

    /**
     * Returns the new xp amount based off where the character is in their leveling.
     */
    public function fetchXP(bool $ignoreCaps = false, float $xpBonus = 0.0): int
    {
        if ($this->currentLevel >= self::HALF && $this->currentLevel < self::THREE_QUARTERS && ! $ignoreCaps) {
            return ceil($this->xp * self::HALF_PERCENT);
        }

        if ($this->currentLevel >= self::THREE_QUARTERS && $this->currentLevel < self::LAST_LEG && ! $ignoreCaps) {
            return ceil($this->xp * self::THREE_QUARTERS_PERCENT);
        }

        if ($this->currentLevel >= self::LAST_LEG && $this->currentLevel < self::MAX_LEVEL && ! $ignoreCaps) {
            return ceil($this->xp * self::LAST_LEG_PERCENT);
        }

        if ($this->currentLevel >= self::MAX_LEVEL) {
            return 0;
        }

        return $this->xp + $this->xp * $xpBonus;
    }
}
