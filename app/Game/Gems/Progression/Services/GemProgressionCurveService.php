<?php

namespace App\Game\Gems\Progression\Services;

use App\Game\Gems\Progression\Values\GemProgressionBands;

/**
 * The one authoritative Gem progression XP curve. Global and personal Gem
 * progression level advancement must resolve XP requirements exclusively
 * through this service; no other class stores or recomputes these formulas.
 */
class GemProgressionCurveService
{
    /**
     * The maximum Gem progression level for the shared global track.
     */
    public function globalMaxLevel(): int
    {
        return GemProgressionBands::GLOBAL_MAX_LEVEL;
    }

    /**
     * The maximum Gem progression level for a Character's personal track.
     */
    public function personalMaxLevel(): int
    {
        return GemProgressionBands::PERSONAL_MAX_LEVEL;
    }

    /**
     * Resolve the XP required to advance from the given current global level to
     * the next level, or null when the global track is already at max level.
     */
    public function xpRequiredForGlobalLevel(int $currentLevel): ?int
    {
        if ($currentLevel >= $this->globalMaxLevel()) {
            return null;
        }

        return $this->roundToNearestHundred($this->globalLevelCost($currentLevel));
    }

    /**
     * Resolve the XP required to advance from the given current personal level to
     * the next level, or null when the personal track is already at max level.
     */
    public function xpRequiredForPersonalLevel(int $currentLevel): ?int
    {
        if ($currentLevel >= $this->personalMaxLevel()) {
            return null;
        }

        if ($currentLevel < GemProgressionBands::PERSONAL_CURVE_MID_LEVEL) {
            return $this->roundToNearestHundred($this->globalLevelCost($currentLevel));
        }

        if ($currentLevel <= GemProgressionBands::PERSONAL_CURVE_HIGH_LEVEL) {
            return $this->roundToNearestHundred($this->personalMidBandCost($currentLevel));
        }

        return $this->roundToNearestHundred($this->personalHighBandCost($currentLevel));
    }

    /**
     * The unrounded global/personal 1-99 curve cost for the given current level.
     */
    private function globalLevelCost(int $currentLevel): float
    {
        return 1000 * (1000 ** (($currentLevel - 1) / 98));
    }

    /**
     * The unrounded personal 100-500 curve cost for the given current level.
     */
    private function personalMidBandCost(int $currentLevel): float
    {
        return 1_000_000 * (10 ** (($currentLevel - 100) / 400));
    }

    /**
     * The unrounded personal 501-999 curve cost for the given current level.
     */
    private function personalHighBandCost(int $currentLevel): float
    {
        return 10_000_000 * (100 ** (($currentLevel - 500) / 499));
    }

    /**
     * Round the given XP requirement to the nearest 100 XP.
     */
    private function roundToNearestHundred(float $value): int
    {
        return intval(round($value / 100)) * 100;
    }
}
