<?php

namespace App\Game\Gems\Progression\Values;

/**
 * Immutable Gem Scroll roll ranges for one personal Gem progression level band.
 */
class GemScrollTierRange
{
    public function __construct(
        private readonly float $xpBonusMin,
        private readonly float $xpBonusMax,
        private readonly float $currencyBonusMin,
        private readonly float $currencyBonusMax,
        private readonly float $itemBonusMin,
        private readonly float $itemBonusMax,
        private readonly int $durationMinutes,
        private readonly float $socketChanceMin,
        private readonly float $socketChanceMax,
        private readonly float $preGemChanceMin,
        private readonly float $preGemChanceMax,
    ) {}

    /**
     * The minimum XP Scroll primary bonus ratio for this tier.
     */
    public function xpBonusMin(): float
    {
        return $this->xpBonusMin;
    }

    /**
     * The maximum XP Scroll primary bonus ratio for this tier.
     */
    public function xpBonusMax(): float
    {
        return $this->xpBonusMax;
    }

    /**
     * The minimum Currency Scroll primary bonus ratio for this tier.
     */
    public function currencyBonusMin(): float
    {
        return $this->currencyBonusMin;
    }

    /**
     * The maximum Currency Scroll primary bonus ratio for this tier.
     */
    public function currencyBonusMax(): float
    {
        return $this->currencyBonusMax;
    }

    /**
     * The minimum Item Scroll primary bonus ratio for this tier.
     */
    public function itemBonusMin(): float
    {
        return $this->itemBonusMin;
    }

    /**
     * The maximum Item Scroll primary bonus ratio for this tier.
     */
    public function itemBonusMax(): float
    {
        return $this->itemBonusMax;
    }

    /**
     * The Scroll duration in minutes for this tier.
     */
    public function durationMinutes(): int
    {
        return $this->durationMinutes;
    }

    /**
     * The minimum Item Scroll socket chance ratio for this tier.
     */
    public function socketChanceMin(): float
    {
        return $this->socketChanceMin;
    }

    /**
     * The maximum Item Scroll socket chance ratio for this tier.
     */
    public function socketChanceMax(): float
    {
        return $this->socketChanceMax;
    }

    /**
     * The minimum Item Scroll pre-gemmed chance ratio for this tier.
     */
    public function preGemChanceMin(): float
    {
        return $this->preGemChanceMin;
    }

    /**
     * The maximum Item Scroll pre-gemmed chance ratio for this tier.
     */
    public function preGemChanceMax(): float
    {
        return $this->preGemChanceMax;
    }
}
