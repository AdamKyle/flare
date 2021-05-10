<?php

namespace App\Game\Battle\Values;

class MaxLevel {

    const MAX_LEVEL      = 1000;
    const HALF           = 500;
    const THREE_QUARTERS = 750;
    const LAST_LEG       = 950;

    const HALF_PERCENT           = 0.25;
    const THREE_QUARTERS_PERCENT = 0.50;
    const LAST_LEG_PERCENT       = 0.75;

    /**
     * @var int $currentLevel
     */
    private $currentLevel = 0;

    /**
     * @var int $xp
     */
    private $xp           = 0;

    /**
     * MaxLevel constructor.
     *
     * @param int $currentLevel
     * @param int $xp
     */
    public function __construct(int $currentLevel, int $xp) {
        $this->currentLevel = $currentLevel;
        $this->xp           = $xp;
    }

    /**
     * Returns the new xp amount based off where the character is in their leveling.
     *
     * @return int
     */
    public function fetchXP(): int {
        if ($this->currentLevel >= self::HALF && $this->currentLevel < self::THREE_QUARTERS) {
            $total = $this->xp * self::HALF_PERCENT;

            return ceil($this->xp - $total);
        }

        if ($this->currentLevel >= self::THREE_QUARTERS && $this->currentLevel < self::LAST_LEG) {
            $total = $this->xp * self::THREE_QUARTERS_PERCENT;

            return ceil($this->xp - $total);
        }

        if ($this->currentLevel >= self::LAST_LEG && $this->currentLevel < self::MAX_LEVEL) {
            $total = $this->xp * self::LAST_LEG_PERCENT;

            return ceil($this->xp - $total);
        }

        if ($this->currentLevel === self::MAX_LEVEL) {
            return 0;
        }

        return $this->xp;
    }
}
