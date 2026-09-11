<?php

namespace App\Game\Gems\Progression\Values;

/**
 * Immutable typed aggregate of a Character's active Gem Scrolls for one
 * exact Map/Location Gem profile.
 */
class GemScrollAggregate
{
    public function __construct(
        private readonly float $totalPrimaryBonus,
        private readonly float $xpBonusTotal,
        private readonly float $goldBonusTotal,
        private readonly float $copperCoinBonusTotal,
        private readonly float $goldDustBonusTotal,
        private readonly float $shardsBonusTotal,
        private readonly float $itemBonusTotal,
        private readonly float $itemSocketChance,
        private readonly float $itemPreGemChance,
        private readonly int $activeCount,
    ) {}

    /**
     * Build an empty aggregate for a Character/profile with no active Scrolls.
     */
    public static function none(): self
    {
        return new self(0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0);
    }

    /**
     * Sum of every active Scroll's primary bonus.
     */
    public function totalPrimaryBonus(): float
    {
        return $this->totalPrimaryBonus;
    }

    /**
     * Sum of active XP Scroll primary bonuses.
     */
    public function xpBonusTotal(): float
    {
        return $this->xpBonusTotal;
    }

    /**
     * Sum of active Currency Scroll primary bonuses targeting Gold.
     */
    public function goldBonusTotal(): float
    {
        return $this->goldBonusTotal;
    }

    /**
     * Sum of active Currency Scroll primary bonuses targeting Copper Coins.
     */
    public function copperCoinBonusTotal(): float
    {
        return $this->copperCoinBonusTotal;
    }

    /**
     * Sum of active Currency Scroll primary bonuses targeting Gold Dust.
     */
    public function goldDustBonusTotal(): float
    {
        return $this->goldDustBonusTotal;
    }

    /**
     * Sum of active Currency Scroll primary bonuses targeting Shards.
     */
    public function shardsBonusTotal(): float
    {
        return $this->shardsBonusTotal;
    }

    /**
     * Sum of active Item Scroll primary bonuses.
     */
    public function itemBonusTotal(): float
    {
        return $this->itemBonusTotal;
    }

    /**
     * Combined active Item Scroll socket chance, clamped to 1.0.
     */
    public function itemSocketChance(): float
    {
        return $this->itemSocketChance;
    }

    /**
     * Combined active Item Scroll pre-gemmed chance, clamped to 1.0.
     */
    public function itemPreGemChance(): float
    {
        return $this->itemPreGemChance;
    }

    /**
     * Count of active Scroll rows contributing to this aggregate.
     */
    public function activeCount(): int
    {
        return $this->activeCount;
    }

    /**
     * The remaining active primary-bonus capacity before the shared 2000% cap is reached.
     */
    public function remainingCapacity(): float
    {
        return max(0.0, GemProgressionBands::ACTIVE_SCROLL_PRIMARY_BONUS_CAP - $this->totalPrimaryBonus);
    }

    /**
     * Determine whether activating a new Scroll with the given primary bonus
     * would stay within the shared 2000% active primary-bonus cap.
     */
    public function canActivate(float $newPrimaryBonus): bool
    {
        return ($this->totalPrimaryBonus + $newPrimaryBonus) <= (GemProgressionBands::ACTIVE_SCROLL_PRIMARY_BONUS_CAP + 0.0000001);
    }
}
