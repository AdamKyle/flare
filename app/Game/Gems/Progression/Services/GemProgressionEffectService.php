<?php

namespace App\Game\Gems\Progression\Services;

use App\Game\Gems\Progression\Values\GemProgressionBands;

/**
 * The one authoritative Gem progression effect math. Resolves the additive
 * global/personal progression bonuses for a given rolled Gem value and
 * global/personal level, following the exact linear bands defined for Gem
 * World progression. This service performs no persistence and no resolution
 * of which Gem source/context a bonus applies to.
 */
class GemProgressionEffectService
{
    /**
     * Resolve the global progression bonus added to one positive rolled Gem
     * effect value at the given global level. Zero when the rolled value is
     * not positive.
     */
    public function globalPositiveBonus(float $originalRolledValue, int $globalLevel): float
    {
        if ($originalRolledValue <= 0.0) {
            return 0.0;
        }

        return $originalRolledValue * $this->clampedProgress($globalLevel - 1, 99);
    }

    /**
     * Resolve the personal 1-100 progression bonus added to one positive
     * rolled Gem effect value at the given personal level. Zero when the
     * rolled value is not positive.
     */
    public function personalBaseBonus(float $originalRolledValue, int $personalLevel): float
    {
        if ($originalRolledValue <= 0.0) {
            return 0.0;
        }

        $cappedLevel = min($personalLevel, GemProgressionBands::PERSONAL_BASE_CAP_LEVEL);

        return $originalRolledValue * $this->clampedProgress($cappedLevel - 1, 99);
    }

    /**
     * Resolve the personal 101-200 flat absolute positive progression bonus
     * for the given personal level. Applies only to a positive rolled Gem
     * effect value that is already contributing.
     */
    public function personalPositiveBandBonus(float $originalRolledValue, int $personalLevel): float
    {
        if ($originalRolledValue <= 0.0) {
            return 0.0;
        }

        return GemProgressionBands::PERSONAL_NEGATIVE_BAND_ONE_INCREMENT
            * $this->clampedProgress($personalLevel - GemProgressionBands::PERSONAL_BASE_CAP_LEVEL, 100);
    }

    /**
     * Resolve the total additive positive effect value for one rolled Gem
     * effect after the global and personal progression bonuses are applied.
     */
    public function effectivePositiveValue(float $originalRolledValue, int $globalLevel, int $personalLevel): float
    {
        return $originalRolledValue
            + $this->globalPositiveBonus($originalRolledValue, $globalLevel)
            + $this->personalBaseBonus($originalRolledValue, $personalLevel)
            + $this->personalPositiveBandBonus($originalRolledValue, $personalLevel);
    }

    /**
     * Resolve the cumulative personal negative progression bonus for the
     * given personal level. Applies only to an existing negative Gem
     * effect/Character power reduction that is already contributing.
     */
    public function personalNegativeBonus(int $personalLevel): float
    {
        $bandOne = $this->clampedProgress($personalLevel - 100, 100) * GemProgressionBands::PERSONAL_NEGATIVE_BAND_ONE_INCREMENT;
        $bandTwo = $this->clampedProgress($personalLevel - 200, 100) * GemProgressionBands::PERSONAL_NEGATIVE_BAND_INCREMENT;
        $bandThree = $this->clampedProgress($personalLevel - 300, 200) * GemProgressionBands::PERSONAL_NEGATIVE_BAND_INCREMENT;
        $bandFour = $this->clampedProgress($personalLevel - 500, 200) * GemProgressionBands::PERSONAL_NEGATIVE_BAND_INCREMENT;
        $bandFive = $this->clampedProgress($personalLevel - 700, 300) * GemProgressionBands::PERSONAL_NEGATIVE_BAND_INCREMENT;

        return $bandOne + $bandTwo + $bandThree + $bandFour + $bandFive;
    }

    /**
     * Resolve the effective negative Gem effect value after the personal
     * negative progression bonus is applied. Zero when the resolved value is
     * not already positive/active.
     */
    public function effectiveNegativeValue(float $resolvedValue, int $personalLevel): float
    {
        if ($resolvedValue <= 0.0) {
            return 0.0;
        }

        return $resolvedValue + $this->personalNegativeBonus($personalLevel);
    }

    /**
     * Resolve the personal Unique/Mythic rarity progression bonus for the
     * given personal level.
     */
    public function personalUniqueMythicBonus(int $personalLevel): float
    {
        if ($personalLevel < GemProgressionBands::PERSONAL_RARITY_UNLOCK_LEVEL) {
            return 0.0;
        }

        if ($personalLevel < GemProgressionBands::PERSONAL_RARITY_MID_LEVEL) {
            return $this->clampedProgress($personalLevel - 200, 100) * GemProgressionBands::PERSONAL_RARITY_UNIQUE_MYTHIC_MID_VALUE;
        }

        return GemProgressionBands::PERSONAL_RARITY_UNIQUE_MYTHIC_MID_VALUE
            + $this->clampedProgress($personalLevel - 300, 200) * (
                GemProgressionBands::PERSONAL_RARITY_UNIQUE_MYTHIC_MAX_VALUE - GemProgressionBands::PERSONAL_RARITY_UNIQUE_MYTHIC_MID_VALUE
            );
    }

    /**
     * Resolve the personal Cosmic rarity progression bonus for the given
     * personal level.
     */
    public function personalCosmicBonus(int $personalLevel): float
    {
        if ($personalLevel < GemProgressionBands::PERSONAL_COSMIC_UNLOCK_LEVEL) {
            return 0.0;
        }

        return GemProgressionBands::PERSONAL_COSMIC_MIN_VALUE
            + $this->clampedProgress($personalLevel - 500, 200) * (
                GemProgressionBands::PERSONAL_COSMIC_MAX_VALUE - GemProgressionBands::PERSONAL_COSMIC_MIN_VALUE
            );
    }

    /**
     * Resolve the personal enhanced-equipment reward opportunity chance for
     * the given personal level.
     */
    public function enhancedEquipmentChance(int $personalLevel): float
    {
        if ($personalLevel < GemProgressionBands::PERSONAL_ENHANCED_EQUIPMENT_LEVEL) {
            return 0.0;
        }

        return GemProgressionBands::PERSONAL_ENHANCED_EQUIPMENT_CHANCE;
    }

    /**
     * Determine whether the given personal level is eligible for the Gem
     * Scroll drop chance.
     */
    public function isScrollDropEligible(int $personalLevel): bool
    {
        return $personalLevel >= GemProgressionBands::PERSONAL_BASE_CAP_LEVEL;
    }

    /**
     * Resolve a 0-1 clamped linear progress ratio for the given numerator/denominator.
     */
    private function clampedProgress(int $numerator, int $denominator): float
    {
        return max(0.0, min(1.0, $numerator / $denominator));
    }
}
