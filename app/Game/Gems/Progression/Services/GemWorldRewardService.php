<?php

namespace App\Game\Gems\Progression\Services;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterBattleRewardRequestStep;
use App\Flare\Models\Item;
use App\Game\BattleRewardProcessing\Services\BattleRewardLedgerService;
use App\Game\Core\Chance\ChanceCalculator;
use App\Game\Core\Chance\RandomNumberGenerator;
use App\Game\Core\Currency\Services\CurrencyLimit;
use App\Game\Core\Events\UpdateCharacterInventoryCountEvent;
use App\Game\Core\Items\Builders\BuildCosmicItem;
use App\Game\Core\Items\Builders\BuildMythicItem;
use App\Game\Core\Items\Builders\BuildUniqueItem;
use App\Game\Core\Items\Values\ItemSocketEligibility;
use App\Game\Gems\Builders\GemBuilder;
use App\Game\Gems\Progression\Values\GemProgressionBands;
use App\Game\Gems\Progression\Values\GemScrollAggregate;
use App\Game\Gems\Progression\Values\ResolvedGemWorldProfile;
use App\Game\Gems\Values\GemTierValue;

/**
 * Owns the Gem World reward pipeline shared by manual battle and Exploration:
 * per-kill Gem progression XP, Gem Scroll drops, level-700+ enhanced
 * equipment, Item Scroll rarity opportunities, and Currency Scroll bonuses.
 * Runs behind the `GEM_WORLD_REWARDS` battle-reward ledger step and begins
 * with the cheapest possible generated-world gate so normal battle rewards
 * stay fast.
 */
class GemWorldRewardService
{
    public function __construct(
        private readonly GemWorldProfileResolver $gemWorldProfileResolver,
        private readonly GemProgressionService $gemProgressionService,
        private readonly GemProgressionEffectService $gemProgressionEffectService,
        private readonly GemScrollEffectService $gemScrollEffectService,
        private readonly GemScrollGenerator $gemScrollGenerator,
        private readonly BattleRewardLedgerService $battleRewardLedgerService,
        private readonly RandomNumberGenerator $randomNumberGenerator,
        private readonly ChanceCalculator $chanceCalculator,
        private readonly ItemSocketEligibility $itemSocketEligibility,
        private readonly GemBuilder $gemBuilder,
        private readonly BuildUniqueItem $buildUniqueItem,
        private readonly BuildMythicItem $buildMythicItem,
        private readonly BuildCosmicItem $buildCosmicItem,
        private readonly GemProgressionBroadcastService $gemProgressionBroadcastService,
    ) {}

    /**
     * Apply the Gem World reward step for one battle reward request. Returns
     * immediately with a no-op result when the Character is not currently
     * inside a generated Gem World, before loading any progression state.
     */
    public function applyToLedgerStep(
        CharacterBattleRewardRequestStep $step,
        Character $character,
        array $effectiveMonster,
        int $qualifyingKills,
        array $earnedCurrencies,
    ): array {
        $resolvedProfile = $this->gemWorldProfileResolver->resolveForCharacter($character);

        if (is_null($resolvedProfile)) {
            return ['applied' => false, 'reason' => 'not_a_gem_world'];
        }

        $checkpoint = $step->checkpoint_json ?? [];

        if (! ($checkpoint['xp_applied'] ?? false)) {
            $checkpoint = array_merge($checkpoint, $this->applyGemXp($character, $resolvedProfile, $effectiveMonster, $qualifyingKills));
            $checkpoint['xp_applied'] = true;
            $step = $this->battleRewardLedgerService->checkpointStep($step, $checkpoint);
        }

        if (! ($checkpoint['rewards_applied'] ?? false)) {
            $checkpoint['rewards_result'] = $this->applyRewards($character->fresh(), $resolvedProfile, $checkpoint['personal_level'], $qualifyingKills);
            $checkpoint['rewards_applied'] = true;
            $step = $this->battleRewardLedgerService->checkpointStep($step, $checkpoint);
        }

        if (! ($checkpoint['currency_scroll_applied'] ?? false)) {
            $checkpoint['currency_scroll_result'] = $this->applyCurrencyScrollBonus($character->fresh(), $resolvedProfile, $earnedCurrencies);
            $checkpoint['currency_scroll_applied'] = true;
            $this->battleRewardLedgerService->checkpointStep($step, $checkpoint);
        }

        $this->gemProgressionBroadcastService->broadcastForProfile(
            $character->fresh(),
            $resolvedProfile,
            $checkpoint['global_level'],
            $checkpoint['global_xp'],
            $checkpoint['personal_level'],
            $checkpoint['personal_xp'],
        );

        return [
            'applied' => true,
            'profile_type' => $resolvedProfile->type()->value,
            'profile_id' => $resolvedProfile->profileId(),
            'personal_level' => $checkpoint['personal_level'],
        ];
    }

    /**
     * Apply global and personal Gem progression XP for this reward request.
     */
    private function applyGemXp(Character $character, ResolvedGemWorldProfile $resolvedProfile, array $effectiveMonster, int $qualifyingKills): array
    {
        $baseGemXpPerKill = intval(round($effectiveMonster['xp'] * GemProgressionBands::GEM_SCROLL_BASE_XP_MULTIPLIER));
        $globalXpTotal = $baseGemXpPerKill * $qualifyingKills;

        $xpScrollBonus = $this->resolveScrollAggregate($character, $resolvedProfile)->xpBonusTotal();
        $personalXpTotal = intval(round($globalXpTotal * (1 + $xpScrollBonus)));

        if ($resolvedProfile->isMapProfile()) {
            $globalResult = $this->gemProgressionService->applyGlobalMapProgressionXp($resolvedProfile->mapProfile(), $globalXpTotal);
            $personalResult = $this->gemProgressionService->applyPersonalMapProgressionXp($character, $resolvedProfile->mapProfile(), $personalXpTotal);
        } else {
            $globalResult = $this->gemProgressionService->applyGlobalLocationProgressionXp($resolvedProfile->locationProfile(), $globalXpTotal);
            $personalResult = $this->gemProgressionService->applyPersonalLocationProgressionXp($character, $resolvedProfile->locationProfile(), $personalXpTotal);
        }

        return [
            'personal_level' => $personalResult->newLevel(),
            'personal_xp' => $personalResult->newXp(),
            'global_level' => $globalResult->newLevel(),
            'global_xp' => $globalResult->newXp(),
            'global_leveled_up' => $globalResult->leveledUp(),
            'personal_leveled_up' => $personalResult->leveledUp(),
        ];
    }

    /**
     * Roll and apply the Gem Scroll drops, level-700+ enhanced equipment,
     * and Item Scroll rarity opportunities for every qualifying kill.
     */
    private function applyRewards(Character $character, ResolvedGemWorldProfile $resolvedProfile, int $personalLevel, int $qualifyingKills): array
    {
        $scrollsDelivered = 0;
        $scrollsLostToFullBag = 0;
        $enhancedItemsDelivered = 0;
        $enhancedItemsLostToFullInventory = 0;
        $itemOpportunityItemsDelivered = 0;
        $itemOpportunityItemsLostToFullInventory = 0;

        $scrollAggregate = $this->resolveScrollAggregate($character, $resolvedProfile);

        for ($kill = 0; $kill < $qualifyingKills; $kill++) {
            $character = $character->fresh();

            if ($this->gemProgressionEffectService->isScrollDropEligible($personalLevel)
                && $this->chanceCalculator->passesPercentage(GemProgressionBands::GEM_SCROLL_DROP_CHANCE * 100)) {
                if ($character->canAddToAlchemyBag()) {
                    $this->deliverScroll($character, $resolvedProfile, $personalLevel);
                    $scrollsDelivered++;
                } else {
                    $scrollsLostToFullBag++;
                }
            }

            if ($personalLevel >= GemProgressionBands::PERSONAL_ENHANCED_EQUIPMENT_LEVEL
                && $this->chanceCalculator->passesPercentage(GemProgressionBands::PERSONAL_ENHANCED_EQUIPMENT_CHANCE * 100)) {
                if ($character->fresh()->isInventoryFull()) {
                    $enhancedItemsLostToFullInventory++;
                } else {
                    $this->deliverEnhancedItem($character);
                    $enhancedItemsDelivered++;
                }
            }

            $itemOpportunities = $this->itemScrollOpportunityCount($scrollAggregate->itemBonusTotal());

            for ($opportunity = 0; $opportunity < $itemOpportunities; $opportunity++) {
                $character = $character->fresh();
                $rarityChance = ($this->gemProgressionEffectService->personalUniqueMythicBonus($personalLevel) + $scrollAggregate->itemBonusTotal()) * 100;

                if (! $this->chanceCalculator->passesPercentage($rarityChance)) {
                    continue;
                }

                if ($character->isInventoryFull()) {
                    $itemOpportunityItemsLostToFullInventory++;

                    continue;
                }

                $this->deliverRarityItem($character, $scrollAggregate);
                $itemOpportunityItemsDelivered++;
            }
        }

        return [
            'scrolls_delivered' => $scrollsDelivered,
            'scrolls_lost_to_full_bag' => $scrollsLostToFullBag,
            'enhanced_items_delivered' => $enhancedItemsDelivered,
            'enhanced_items_lost_to_full_inventory' => $enhancedItemsLostToFullInventory,
            'item_opportunity_items_delivered' => $itemOpportunityItemsDelivered,
            'item_opportunity_items_lost_to_full_inventory' => $itemOpportunityItemsLostToFullInventory,
        ];
    }

    /**
     * Generate one Gem Scroll Item and deliver it to the Character's Alchemy Bag.
     */
    private function deliverScroll(Character $character, ResolvedGemWorldProfile $resolvedProfile, int $personalLevel): void
    {
        $scrollItem = $this->gemScrollGenerator->generateForPersonalLevel($personalLevel);

        $character->alchemyBag->slots()->create([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $scrollItem->id,
            'amount' => 1,
        ]);

        event(new UpdateCharacterInventoryCountEvent($character));
    }

    /**
     * Build one random Unique/Mythic/Cosmic enhanced equipment Item, apply
     * sockets/pre-gemmed Tier Four Gems, and deliver it to the Character's inventory.
     */
    private function deliverEnhancedItem(Character $character): void
    {
        $item = $this->buildRandomRarityItem($character);

        if (is_null($item)) {
            return;
        }

        if ($this->itemSocketEligibility->isEligible($item->type)) {
            $socketCount = $this->randomNumberGenerator->numberBetween(1, $this->itemSocketEligibility->maxSocketCount());
            $item->update(['socket_count' => $socketCount]);
            $item = $item->refresh();

            $gemCount = $this->randomNumberGenerator->numberBetween(1, $socketCount);
            $this->attachTierFourGems($item, $gemCount);
        }

        $character->inventory->slots()->create([
            'inventory_id' => $character->inventory->id,
            'item_id' => $item->id,
        ]);

        event(new UpdateCharacterInventoryCountEvent($character));
    }

    /**
     * Build one random Unique/Mythic/Cosmic Item Scroll rarity opportunity
     * Item, apply the active Item Scroll socket/pre-gemmed chances, and
     * deliver it to the Character's inventory.
     */
    private function deliverRarityItem(Character $character, GemScrollAggregate $scrollAggregate): void
    {
        $item = $this->buildRandomRarityItem($character);

        if (is_null($item)) {
            return;
        }

        if ($this->itemSocketEligibility->isEligible($item->type)
            && $this->chanceCalculator->passesPercentage($scrollAggregate->itemSocketChance() * 100)) {
            $socketCount = $this->randomNumberGenerator->numberBetween(1, $this->itemSocketEligibility->maxSocketCount());
            $item->update(['socket_count' => $socketCount]);
            $item = $item->refresh();

            if ($this->chanceCalculator->passesPercentage($scrollAggregate->itemPreGemChance() * 100) && $item->socket_count > 0) {
                $gemCount = $this->randomNumberGenerator->numberBetween(1, $item->socket_count);
                $this->attachTierFourGems($item, $gemCount);
            }
        }

        $character->inventory->slots()->create([
            'inventory_id' => $character->inventory->id,
            'item_id' => $item->id,
        ]);

        event(new UpdateCharacterInventoryCountEvent($character));
    }

    /**
     * Build one random Unique/Mythic/Cosmic Item with equal probability using
     * the existing Item builders.
     */
    private function buildRandomRarityItem(Character $character): ?Item
    {
        return match ($this->randomNumberGenerator->numberBetween(1, 3)) {
            1 => $this->buildUniqueItem->fetchUniqueItem($character),
            2 => $this->buildMythicItem->fetchMythicItem($character),
            default => $this->buildCosmicItem->fetchCosmicItem($character),
        };
    }

    /**
     * Generate and attach the given number of Tier Four Character Gems to the Item's sockets.
     */
    private function attachTierFourGems(Item $item, int $gemCount): void
    {
        for ($index = 0; $index < $gemCount; $index++) {
            $gem = $this->gemBuilder->buildGem(GemTierValue::TIER_FOUR);

            $item->sockets()->create([
                'item_id' => $item->id,
                'gem_id' => $gem->id,
            ]);
        }

        $item->update(['has_gems_socketed' => true]);
    }

    /**
     * Resolve the number of additional Item Scroll rarity opportunities
     * granted by the combined active Item Scroll primary bonus, capped at five.
     */
    private function itemScrollOpportunityCount(float $itemBonusTotal): int
    {
        if ($itemBonusTotal < 1.0) {
            return 0;
        }

        return min(GemProgressionBands::ITEM_SCROLL_MAX_BONUS_OPPORTUNITIES, intval(floor($itemBonusTotal)));
    }

    /**
     * Apply the active Currency Scroll bonus on top of the currency amounts
     * already awarded by the CURRENCY_REWARDS step this request, respecting
     * the existing Character currency caps and without re-paying the base reward.
     */
    private function applyCurrencyScrollBonus(Character $character, ResolvedGemWorldProfile $resolvedProfile, array $earnedCurrencies): array
    {
        $scrollAggregate = $this->resolveScrollAggregate($character, $resolvedProfile);

        $goldBonus = $this->currencyScrollBonusAmount($earnedCurrencies['gold'] ?? 0, $scrollAggregate->goldBonusTotal());
        $goldDustBonus = $this->currencyScrollBonusAmount($earnedCurrencies['gold_dust'] ?? 0, $scrollAggregate->goldDustBonusTotal());
        $shardsBonus = $this->currencyScrollBonusAmount($earnedCurrencies['shards'] ?? 0, $scrollAggregate->shardsBonusTotal());
        $copperCoinsBonus = $this->currencyScrollBonusAmount($earnedCurrencies['copper_coins'] ?? 0, $scrollAggregate->copperCoinBonusTotal());

        $character->update([
            'gold' => min($character->gold + $goldBonus, CurrencyLimit::MAX_GOLD),
            'gold_dust' => min($character->gold_dust + $goldDustBonus, CurrencyLimit::MAX_GOLD_DUST),
            'shards' => min($character->shards + $shardsBonus, CurrencyLimit::MAX_SHARDS),
            'copper_coins' => min($character->copper_coins + $copperCoinsBonus, CurrencyLimit::MAX_COPPER),
        ]);

        return [
            'gold' => $goldBonus,
            'gold_dust' => $goldDustBonus,
            'shards' => $shardsBonus,
            'copper_coins' => $copperCoinsBonus,
        ];
    }

    /**
     * Resolve the extra currency amount granted by an active Currency Scroll bonus.
     */
    private function currencyScrollBonusAmount(int $baseAmount, float $bonusRatio): int
    {
        if ($bonusRatio <= 0.0 || $baseAmount <= 0) {
            return 0;
        }

        return intval(round($baseAmount * $bonusRatio));
    }

    /**
     * Resolve the active Gem Scroll aggregate for the Character's current exact profile.
     */
    private function resolveScrollAggregate(Character $character, ResolvedGemWorldProfile $resolvedProfile): GemScrollAggregate
    {
        if ($resolvedProfile->isMapProfile()) {
            return $this->gemScrollEffectService->aggregateForMapProfile($character, $resolvedProfile->mapProfile());
        }

        return $this->gemScrollEffectService->aggregateForLocationProfile($character, $resolvedProfile->locationProfile());
    }
}
