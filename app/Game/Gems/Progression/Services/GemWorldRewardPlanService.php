<?php

namespace App\Game\Gems\Progression\Services;

use App\Game\Core\Chance\ChanceCalculator;
use App\Game\Core\Chance\RandomNumberGenerator;
use App\Game\Core\Items\Values\ItemSocketEligibility;
use App\Game\Gems\Progression\Values\GemItemRarity;
use App\Game\Gems\Progression\Values\GemProgressionBands;
use App\Game\Gems\Progression\Values\GemScrollAggregate;
use App\Game\Gems\Progression\Values\GemScrollRollPlan;
use App\Game\Gems\Progression\Values\GemSpecialItemRollPlan;
use App\Game\Gems\Progression\Values\GemWorldKillRewardPlan;
use App\Game\Gems\Progression\Values\GemWorldRewardPlan;

class GemWorldRewardPlanService
{
    /**
     * @param GemProgressionEffectService $gemProgressionEffectService
     * @param GemScrollGenerator $gemScrollGenerator
     * @param RandomNumberGenerator $randomNumberGenerator
     * @param ChanceCalculator $chanceCalculator
     */
    public function __construct(
        private readonly GemProgressionEffectService $gemProgressionEffectService,
        private readonly GemScrollGenerator $gemScrollGenerator,
        private readonly RandomNumberGenerator $randomNumberGenerator,
        private readonly ChanceCalculator $chanceCalculator,
    ) {}

    /**
     * Roll the complete reward plan for every qualifying kill in this request.
     *
     * @param int $personalLevel
     * @param int $qualifyingKills
     * @param GemScrollAggregate $scrollAggregate
     * @return GemWorldRewardPlan
     */
    public function plan(int $personalLevel, int $qualifyingKills, GemScrollAggregate $scrollAggregate): GemWorldRewardPlan
    {
        $kills = [];

        for ($kill = 0; $kill < $qualifyingKills; $kill++) {
            $kills[] = $this->planKill($personalLevel, $scrollAggregate);
        }

        return new GemWorldRewardPlan($kills);
    }

    /**
     * Roll the complete reward plan for one qualifying kill.
     *
     * @param int $personalLevel
     * @param GemScrollAggregate $scrollAggregate
     * @return GemWorldKillRewardPlan
     */
    private function planKill(int $personalLevel, GemScrollAggregate $scrollAggregate): GemWorldKillRewardPlan
    {
        return new GemWorldKillRewardPlan(
            $this->planScrollRoll($personalLevel),
            $this->planEnhancedItemRoll($personalLevel),
            $this->planItemOpportunityRolls($personalLevel, $scrollAggregate),
        );
    }

    /**
     * Roll the fixed 2% Gem Scroll drop chance for one kill, once the Character is eligible for Scroll rewards at all.
     *
     * @param int $personalLevel
     * @return GemScrollRollPlan
     */
    private function planScrollRoll(int $personalLevel): GemScrollRollPlan
    {
        if (! $this->gemProgressionEffectService->isScrollDropEligible($personalLevel)) {
            return GemScrollRollPlan::noDrop();
        }

        if (! $this->chanceCalculator->passesPercentage(GemProgressionBands::GEM_SCROLL_DROP_CHANCE * 100)) {
            return GemScrollRollPlan::noDrop();
        }

        return $this->gemScrollGenerator->planForPersonalLevel($personalLevel);
    }

    /**
     * Roll the level-700+ enhanced-equipment reward opportunity for one kill, guaranteed sockets and Tier Four Gems on success.
     *
     * @param int $personalLevel
     * @return GemSpecialItemRollPlan|null
     */
    private function planEnhancedItemRoll(int $personalLevel): ?GemSpecialItemRollPlan
    {
        if ($personalLevel < GemProgressionBands::PERSONAL_ENHANCED_EQUIPMENT_LEVEL) {
            return null;
        }

        $candidateRarity = $this->rollCandidateRarity();

        if (! $this->chanceCalculator->passesPercentage(GemProgressionBands::PERSONAL_ENHANCED_EQUIPMENT_CHANCE * 100)) {
            return GemSpecialItemRollPlan::failed($candidateRarity);
        }

        $socketCount = $this->randomNumberGenerator->numberBetween(1, ItemSocketEligibility::MAX_SOCKET_COUNT);
        $gemCount = $this->randomNumberGenerator->numberBetween(1, $socketCount);

        return new GemSpecialItemRollPlan($candidateRarity, true, true, $socketCount, true, $gemCount);
    }

    /**
     * Roll every additional Item Scroll rarity opportunity granted for one kill by the combined active Item Scroll primary bonus.
     *
     * @param int $personalLevel
     * @param GemScrollAggregate $scrollAggregate
     * @return array
     */
    private function planItemOpportunityRolls(int $personalLevel, GemScrollAggregate $scrollAggregate): array
    {
        $opportunityCount = $this->itemScrollOpportunityCount($scrollAggregate->itemBonusTotal());
        $rolls = [];

        for ($opportunity = 0; $opportunity < $opportunityCount; $opportunity++) {
            $rolls[] = $this->planItemOpportunityRoll($personalLevel, $scrollAggregate);
        }

        return $rolls;
    }

    /**
     * Roll one Item Scroll rarity opportunity: a candidate rarity, its personal-progression chance plus the active Item Scroll bonus, and its independent socket/pre-gem secondary chances.
     *
     * @param int $personalLevel
     * @param GemScrollAggregate $scrollAggregate
     * @return GemSpecialItemRollPlan
     */
    private function planItemOpportunityRoll(int $personalLevel, GemScrollAggregate $scrollAggregate): GemSpecialItemRollPlan
    {
        $candidateRarity = $this->rollCandidateRarity();
        $rarityChance = ($this->personalRarityChance($candidateRarity, $personalLevel) + $scrollAggregate->itemBonusTotal()) * 100;

        if (! $this->chanceCalculator->passesPercentage($rarityChance)) {
            return GemSpecialItemRollPlan::failed($candidateRarity);
        }

        $socketed = $this->chanceCalculator->passesPercentage($scrollAggregate->itemSocketChance() * 100);
        $socketCount = null;
        $preGemmed = false;
        $gemCount = null;

        if ($socketed) {
            $socketCount = $this->randomNumberGenerator->numberBetween(1, ItemSocketEligibility::MAX_SOCKET_COUNT);
            $preGemmed = $this->chanceCalculator->passesPercentage($scrollAggregate->itemPreGemChance() * 100);

            if ($preGemmed) {
                $gemCount = $this->randomNumberGenerator->numberBetween(1, $socketCount);
            }
        }

        return new GemSpecialItemRollPlan($candidateRarity, true, $socketed, $socketCount, $preGemmed, $gemCount);
    }

    /**
     * Randomly choose one candidate rarity with equal probability.
     *
     * @return GemItemRarity
     */
    private function rollCandidateRarity(): GemItemRarity
    {
        return match ($this->randomNumberGenerator->numberBetween(1, 3)) {
            1 => GemItemRarity::UNIQUE,
            2 => GemItemRarity::MYTHIC,
            default => GemItemRarity::COSMIC,
        };
    }

    /**
     * Resolve the personal progression chance owned by the given candidate rarity.
     *
     * @param GemItemRarity $candidateRarity
     * @param int $personalLevel
     * @return float
     */
    private function personalRarityChance(GemItemRarity $candidateRarity, int $personalLevel): float
    {
        return match ($candidateRarity) {
            GemItemRarity::UNIQUE, GemItemRarity::MYTHIC => $this->gemProgressionEffectService->personalUniqueMythicBonus($personalLevel),
            GemItemRarity::COSMIC => $this->gemProgressionEffectService->personalCosmicBonus($personalLevel),
        };
    }

    /**
     * Resolve the number of additional Item Scroll rarity opportunities granted by the combined active Item Scroll primary bonus, capped at five.
     *
     * @param float $itemBonusTotal
     * @return int
     */
    private function itemScrollOpportunityCount(float $itemBonusTotal): int
    {
        if ($itemBonusTotal < 1.0) {
            return 0;
        }

        return min(GemProgressionBands::ITEM_SCROLL_MAX_BONUS_OPPORTUNITIES, intval(floor($itemBonusTotal)));
    }
}
