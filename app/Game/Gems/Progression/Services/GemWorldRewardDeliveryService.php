<?php

namespace App\Game\Gems\Progression\Services;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterBattleRewardRequestStep;
use App\Flare\Models\Item;
use App\Game\Battle\Services\BattleDrop;
use App\Game\BattleRewardProcessing\Services\BattleRewardLedgerService;
use App\Game\Core\Events\UpdateCharacterInventoryCountEvent;
use App\Game\Core\Items\Builders\BuildCosmicItem;
use App\Game\Core\Items\Builders\BuildMythicItem;
use App\Game\Core\Items\Builders\BuildUniqueItem;
use App\Game\Core\Items\Values\ItemSocketEligibility;
use App\Game\Gems\Builders\GemBuilder;
use App\Game\Gems\Progression\Values\GemItemRarity;
use App\Game\Gems\Progression\Values\GemScrollRollPlan;
use App\Game\Gems\Progression\Values\GemSpecialItemRollPlan;
use App\Game\Gems\Progression\Values\GemWorldKillRewardPlan;
use App\Game\Gems\Progression\Values\GemWorldRewardPlan;
use App\Game\Gems\Values\GemTierValue;
use Illuminate\Support\Facades\DB;

class GemWorldRewardDeliveryService
{
    /**
     * @param BattleRewardLedgerService $battleRewardLedgerService
     * @param GemScrollGenerator $gemScrollGenerator
     * @param ItemSocketEligibility $itemSocketEligibility
     * @param GemBuilder $gemBuilder
     * @param BuildUniqueItem $buildUniqueItem
     * @param BuildMythicItem $buildMythicItem
     * @param BuildCosmicItem $buildCosmicItem
     * @param BattleDrop $battleDrop
     */
    public function __construct(
        private readonly BattleRewardLedgerService $battleRewardLedgerService,
        private readonly GemScrollGenerator $gemScrollGenerator,
        private readonly ItemSocketEligibility $itemSocketEligibility,
        private readonly GemBuilder $gemBuilder,
        private readonly BuildUniqueItem $buildUniqueItem,
        private readonly BuildMythicItem $buildMythicItem,
        private readonly BuildCosmicItem $buildCosmicItem,
        private readonly BattleDrop $battleDrop,
    ) {}

    /**
     * Deliver every not-yet-delivered planned reward, resuming from the
     * checkpointed delivery cursor. Returns the final delivery tally.
     *
     * @param CharacterBattleRewardRequestStep $step
     * @param Character $character
     * @param GemWorldRewardPlan $plan
     * @param array $checkpoint
     * @return array
     */
    public function deliver(CharacterBattleRewardRequestStep $step, Character $character, GemWorldRewardPlan $plan, array $checkpoint): array
    {
        $units = $this->flattenUnits($plan);
        $tally = $checkpoint['rewards_tally'] ?? $this->emptyTally();
        $deliveredItemIds = $checkpoint['rewards_delivered_item_ids'] ?? [];
        $inventoryChanged = false;

        for ($index = $checkpoint['rewards_delivered_through'] ?? 0; $index < count($units); $index++) {
            $outcome = $this->deliverUnitTransactionally($step, $character, $units[$index], $index, $tally, $deliveredItemIds, $checkpoint);

            if (is_null($outcome)) {
                break;
            }

            $inventoryChanged = $inventoryChanged || $outcome;
        }

        if ($inventoryChanged) {
            event(new UpdateCharacterInventoryCountEvent($character->fresh()));
        }

        return $tally;
    }

    /**
     * Deliver one planned reward unit and advance its ledger checkpoint in a
     * single locked transaction. Returns null when a concurrent process has
     * already advanced past this unit, true when inventory/Alchemy Bag
     * state changed, false when the reward was rolled but lost to capacity.
     *
     * @param CharacterBattleRewardRequestStep $step
     * @param Character $character
     * @param array $unit
     * @param int $index
     * @param array $tally
     * @param array $deliveredItemIds
     * @param array $checkpoint
     * @return ?bool
     */
    private function deliverUnitTransactionally(
        CharacterBattleRewardRequestStep $step,
        Character $character,
        array $unit,
        int $index,
        array &$tally,
        array &$deliveredItemIds,
        array &$checkpoint,
    ): ?bool {
        return DB::transaction(function () use ($step, $character, $unit, $index, &$tally, &$deliveredItemIds, &$checkpoint): ?bool {
            $lockedStep = CharacterBattleRewardRequestStep::where('id', $step->id)->lockForUpdate()->first();
            $liveCheckpoint = $lockedStep->checkpoint_json ?? [];

            if (($liveCheckpoint['rewards_delivered_through'] ?? 0) > $index) {
                return null;
            }

            $delivered = $this->deliverUnit($character->fresh(), $unit, $tally, $deliveredItemIds);

            $checkpoint['rewards_delivered_through'] = $index + 1;
            $checkpoint['rewards_tally'] = $tally;
            $checkpoint['rewards_delivered_item_ids'] = $deliveredItemIds;

            $this->battleRewardLedgerService->checkpointStep($lockedStep, $checkpoint);

            return $delivered;
        });
    }

    /**
     * Flatten a plan's per-kill rolls into an ordered, stable list of
     * delivery units, skipping every roll that did not succeed.
     *
     * @param GemWorldRewardPlan $plan
     * @return array
     */
    private function flattenUnits(GemWorldRewardPlan $plan): array
    {
        $units = [];

        foreach ($plan->kills() as $kill) {
            array_push($units, ...$this->unitsForKill($kill));
        }

        return $units;
    }

    /**
     * Resolve the delivery units contributed by one kill's plan.
     *
     * @param GemWorldKillRewardPlan $kill
     * @return array
     */
    private function unitsForKill(GemWorldKillRewardPlan $kill): array
    {
        $units = [];

        if ($kill->scrollRoll()->dropped()) {
            $units[] = ['type' => 'scroll', 'roll' => $kill->scrollRoll()];
        }

        if (! is_null($kill->enhancedItemRoll()) && $kill->enhancedItemRoll()->succeeded()) {
            $units[] = ['type' => 'enhanced_item', 'roll' => $kill->enhancedItemRoll()];
        }

        foreach ($kill->itemOpportunityRolls() as $opportunityRoll) {
            if ($opportunityRoll->succeeded()) {
                $units[] = ['type' => 'item_opportunity', 'roll' => $opportunityRoll];
            }
        }

        return $units;
    }

    /**
     * Deliver one flattened delivery unit, mutating the running tally and
     * delivered-Item-id list. Returns whether inventory/Alchemy Bag state changed.
     *
     * @param Character $character
     * @param array $unit
     * @param array $tally
     * @param array $deliveredItemIds
     * @return bool
     */
    private function deliverUnit(Character $character, array $unit, array &$tally, array &$deliveredItemIds): bool
    {
        return match ($unit['type']) {
            'scroll' => $this->deliverScroll($character, $unit['roll'], $tally, $deliveredItemIds),
            'enhanced_item' => $this->deliverSpecialItem($character, $unit['roll'], $tally, $deliveredItemIds, true),
            'item_opportunity' => $this->deliverSpecialItem($character, $unit['roll'], $tally, $deliveredItemIds, false),
        };
    }

    /**
     * Generate and deliver one planned Gem Scroll Item to the Character's Alchemy Bag.
     *
     * @param Character $character
     * @param GemScrollRollPlan $roll
     * @param array $tally
     * @param array $deliveredItemIds
     * @return bool
     */
    private function deliverScroll(Character $character, GemScrollRollPlan $roll, array &$tally, array &$deliveredItemIds): bool
    {
        if (! $character->canAddToAlchemyBag()) {
            $tally['scrolls_lost_to_full_bag']++;

            return false;
        }

        $scrollItem = $this->gemScrollGenerator->createFromPlan($roll);

        $character->alchemyBag->slots()->create([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $scrollItem->id,
            'amount' => 1,
        ]);

        $tally['scrolls_delivered']++;
        $deliveredItemIds[] = $scrollItem->id;

        return true;
    }

    /**
     * Build and deliver one planned special-item reward (level-700+
     * enhanced equipment, or an Item Scroll rarity opportunity) to the
     * Character's inventory. Returns whether the Item was actually delivered.
     *
     * @param Character $character
     * @param GemSpecialItemRollPlan $roll
     * @param array $tally
     * @param array $deliveredItemIds
     * @param bool $isEnhanced
     * @return bool
     */
    private function deliverSpecialItem(Character $character, GemSpecialItemRollPlan $roll, array &$tally, array &$deliveredItemIds, bool $isEnhanced): bool
    {
        if ($character->isInventoryFull()) {
            $tally[$isEnhanced ? 'enhanced_items_lost_to_full_inventory' : 'item_opportunity_items_lost_to_full_inventory']++;

            return false;
        }

        $onlyTypes = $isEnhanced ? $this->itemSocketEligibility->eligibleTypes() : [];
        $item = $this->buildRarityItem($character, $roll->candidateRarity(), $onlyTypes);
        $item = $this->applySocketsAndGems($item, $roll);

        $this->battleDrop->applyPlannedItem($character, $item->id);

        $tally[$isEnhanced ? 'enhanced_items_delivered' : 'item_opportunity_items_delivered']++;
        $deliveredItemIds[] = $item->id;

        return true;
    }

    /**
     * Apply the planned socket count and pre-gemmed Tier Four Gems to a
     * delivered Item, when the roll called for them and the Item is eligible.
     *
     * @param Item $item
     * @param GemSpecialItemRollPlan $roll
     * @return Item
     */
    private function applySocketsAndGems(Item $item, GemSpecialItemRollPlan $roll): Item
    {
        if (! $roll->socketed() || ! $this->itemSocketEligibility->isEligible($item->type)) {
            return $item;
        }

        $item->update(['socket_count' => $roll->socketCount()]);
        $item = $item->refresh();

        if ($roll->preGemmed() && $item->socket_count > 0) {
            $this->attachTierFourGems($item, min($roll->gemCount(), $item->socket_count));
        }

        return $item;
    }

    /**
     * Build one Item of the given candidate rarity using the existing rarity builders.
     *
     * @param Character $character
     * @param GemItemRarity $rarity
     * @param array $onlyTypes
     * @return Item
     */
    private function buildRarityItem(Character $character, GemItemRarity $rarity, array $onlyTypes): Item
    {
        return match ($rarity) {
            GemItemRarity::UNIQUE => $this->buildUniqueItem->fetchUniqueItem($character, $onlyTypes),
            GemItemRarity::MYTHIC => $this->buildMythicItem->fetchMythicItem($character, $onlyTypes),
            GemItemRarity::COSMIC => $this->buildCosmicItem->fetchCosmicItem($character, $onlyTypes),
        };
    }

    /**
     * Generate and attach the given number of Tier Four Character Gems to the Item's sockets.
     *
     * @param Item $item
     * @param int $gemCount
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
     * The zero-value delivery tally used when no checkpoint tally exists yet.
     *
     * @return array
     */
    private function emptyTally(): array
    {
        return [
            'scrolls_delivered' => 0,
            'scrolls_lost_to_full_bag' => 0,
            'enhanced_items_delivered' => 0,
            'enhanced_items_lost_to_full_inventory' => 0,
            'item_opportunity_items_delivered' => 0,
            'item_opportunity_items_lost_to_full_inventory' => 0,
        ];
    }
}
