<?php

namespace App\Game\Automation\BatchCrafting\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingOutputDestination;
use App\Game\Automation\BatchCrafting\Values\BatchCraftingOperationResult;
use App\Game\Market\Services\MarketBoard;
use App\Game\Messages\Handlers\ServerMessageHandler;
use App\Game\Skills\Services\CraftingService;
use App\Game\Skills\Services\DisenchantService;
use App\Game\Skills\Services\EnchantingService;
use App\Game\Skills\Values\CraftingMessageMode;
use Closure;

class CraftAndEnchantBatchAttemptService
{
    public function __construct(
        private readonly CraftingService $craftingService,
        private readonly EnchantingService $enchantingService,
        private readonly CraftingBatchAttemptService $craftingBatchAttemptService,
        private readonly MarketBoard $marketBoard,
        private readonly DisenchantService $disenchantService,
        private readonly ServerMessageHandler $serverMessageHandler,
    ) {}

    /**
     * Craft one item and apply the requested enchantment, then apply the given disposition.
     *
     * @param  Character  $character  The character running the batch.
     * @param  BatchCraftingDisposition  $disposition  The configured disposition (Keep, Sell, Destroy, List, or Disenchant).
     * @param  Item  $item  The item to craft.
     * @param  string  $craftingType  The crafting type used to craft the item.
     * @param  int|null  $prefixId  The requested Prefix affix id, when selected.
     * @param  int|null  $suffixId  The requested Suffix affix id, when selected.
     * @param  Closure|null  $placeItem  The retained-item placement callback, required only for Keep.
     * @param  int|null  $listingPrice  The requested Market listing price, required only for List.
     * @return BatchCraftingOperationResult The outcome of the attempt.
     */
    public function attempt(
        Character $character,
        BatchCraftingDisposition $disposition,
        Item $item,
        string $craftingType,
        ?int $prefixId,
        ?int $suffixId,
        ?Closure $placeItem,
        ?int $listingPrice,
    ): BatchCraftingOperationResult {
        $craftAndEnchant = $this->craftAndEnchant($character, $item, $craftingType, $prefixId, $suffixId);

        if (! $craftAndEnchant['success']) {
            return $craftAndEnchant['result'];
        }

        $result = $this->applyDisposition($character, $disposition, $craftAndEnchant['item'], $placeItem, $listingPrice, $craftAndEnchant['gold_cost']);

        return $result->withXpGained($craftAndEnchant['xp_gained']);
    }

    /**
     * Apply one of the five plain dispositions to an already crafted and enchanted item.
     *
     * Shared by the plain attempt() flow and by workflows that must apply a disposition after
     * comparing the finished item against other criteria (for example, Keep Best).
     *
     * @param  Character  $character  The character running the batch.
     * @param  BatchCraftingDisposition  $disposition  The configured disposition (Keep, Sell, Destroy, List, or Disenchant).
     * @param  Item  $item  The finished enchanted item.
     * @param  Closure|null  $placeItem  The retained-item placement callback, required only for Keep.
     * @param  int|null  $listingPrice  The requested Market listing price, required only for List.
     * @param  int  $goldCost  The total Gold cost already spent on this item.
     * @return BatchCraftingOperationResult The outcome of applying the disposition.
     */
    public function applyDisposition(Character $character, BatchCraftingDisposition $disposition, Item $item, ?Closure $placeItem, ?int $listingPrice, int $goldCost): BatchCraftingOperationResult
    {
        return match ($disposition) {
            BatchCraftingDisposition::KEEP => $this->applyKeep($character, $item, $placeItem, $goldCost),
            BatchCraftingDisposition::SELL => $this->applySell($character, $item, $goldCost),
            BatchCraftingDisposition::DESTROY => $this->applyDestroy($character, $item, $goldCost),
            BatchCraftingDisposition::LIST => $this->applyList($character, $item, $listingPrice, $goldCost),
            BatchCraftingDisposition::DISENCHANT => $this->applyDisenchant($character, $goldCost),
        };
    }

    /**
     * Craft one item and apply the requested enchantment, without applying any disposition.
     *
     * Shared by the plain attempt() flow and by Keep Best callers that must inspect the
     * finished enchanted item's quality before deciding how to dispose of it.
     *
     * @param  Character  $character  The character running the batch.
     * @param  Item  $item  The item to craft.
     * @param  string  $craftingType  The crafting type used to craft the item.
     * @param  int|null  $prefixId  The requested Prefix affix id, when selected.
     * @param  int|null  $suffixId  The requested Suffix affix id, when selected.
     * @return array{success: bool, item: Item|null, gold_cost: int, xp_gained: int, result: BatchCraftingOperationResult|null} The craft and enchant outcome.
     */
    public function craftAndEnchant(Character $character, Item $item, string $craftingType, ?int $prefixId, ?int $suffixId): array
    {
        $craftGoldCost = $this->craftingService->getItemCostForAutomation($character, $item);
        $craftResult = $this->craftingService->craftForBatch($character, $item, $craftingType, CraftingMessageMode::BATCH_CRAFTING);

        if (! $craftResult['success']) {
            return [
                'success' => false,
                'item' => null,
                'gold_cost' => $craftGoldCost,
                'xp_gained' => 0,
                'result' => $this->craftingBatchAttemptService->translateFailure($craftResult['reason'], $craftGoldCost),
            ];
        }

        $craftedItem = $craftResult['item'];
        $xpGained = $craftResult['xp_gained'];

        $affixes = $this->enchantingService->resolveBatchAffixes($character, $prefixId, $suffixId);

        if (! is_null($affixes['error'])) {
            return [
                'success' => false,
                'item' => null,
                'gold_cost' => $craftGoldCost,
                'xp_gained' => $xpGained,
                'result' => BatchCraftingOperationResult::failed($craftGoldCost)->withXpGained($xpGained),
            ];
        }

        $affixIds = array_values(array_filter([$affixes['prefix']?->id, $affixes['suffix']?->id]));
        $enchantGoldCost = $this->enchantingService->getCostOfEnchantment($character, $affixIds, $craftedItem->id);
        $enchantResult = $this->enchantingService->enchantItemForBatch($character, $craftedItem, $affixIds, $enchantGoldCost, true);
        $totalGoldCost = $craftGoldCost + $enchantGoldCost;

        if (! $enchantResult['success']) {
            return [
                'success' => false,
                'item' => null,
                'gold_cost' => $totalGoldCost,
                'xp_gained' => $xpGained,
                'result' => BatchCraftingOperationResult::failed($totalGoldCost)->withXpGained($xpGained),
            ];
        }

        return [
            'success' => true,
            'item' => $enchantResult['item'],
            'gold_cost' => $totalGoldCost,
            'xp_gained' => $xpGained,
            'result' => null,
        ];
    }

    /**
     * Sell a displaced enchanted item on behalf of a disposition that discards it.
     *
     * @param  Character  $character  The character who crafted the item.
     * @param  Item  $item  The item being sold.
     * @return int The Gold gained from the sale.
     */
    public function sellForDisplacement(Character $character, Item $item): int
    {
        return $this->craftingBatchAttemptService->sellForDisplacement($character, $item);
    }

    /**
     * Destroy a displaced enchanted item on behalf of a disposition that discards it.
     *
     * @param  Character  $character  The character who crafted the item.
     * @param  Item  $item  The item being destroyed.
     * @return void This method does not return a value.
     */
    public function destroyForDisplacement(Character $character, Item $item): void
    {
        $this->craftingBatchAttemptService->destroyForDisplacement($character, $item);
    }

    /**
     * Disenchant a displaced enchanted item on behalf of a disposition that discards it.
     *
     * @param  Character  $character  The character who crafted the item.
     * @return void This method does not return a value.
     */
    public function disenchantForDisplacement(Character $character): void
    {
        $this->disenchantService->setUp($character)->disenchantBatchCraftedItem();
    }

    /**
     * Send the Keep server message and record the kept crafting outcome.
     *
     * @param  Character  $character  The character who crafted the item.
     * @param  Item  $item  The enchanted item.
     * @param  Closure|null  $placeItem  The retained-item placement callback.
     * @param  int  $goldCost  The total Gold cost of this attempt.
     * @return BatchCraftingOperationResult The kept operation result.
     */
    private function applyKeep(Character $character, Item $item, ?Closure $placeItem, int $goldCost): BatchCraftingOperationResult
    {
        $destination = $placeItem($item);

        if (is_null($destination)) {
            return BatchCraftingOperationResult::failed($goldCost);
        }

        $itemName = $item->affix_name ?? $item->name;
        $destinationEnum = BatchCraftingOutputDestination::from($destination['destination']);

        $keptMessage = match ($destinationEnum) {
            BatchCraftingOutputDestination::INVENTORY => 'Kept: '.$itemName.' in your Inventory.',
            BatchCraftingOutputDestination::CRAFTED_ITEMS_SET => 'Kept: '.$itemName.' in your Crafted Items Set.',
            BatchCraftingOutputDestination::INVENTORY_SET => 'Kept: '.$itemName.' in your Set.',
        };

        $this->serverMessageHandler->sendBasicMessageWithLink($character->user, $keptMessage, $destination['id'], $destinationEnum->value, $itemName);

        return BatchCraftingOperationResult::kept($goldCost);
    }

    /**
     * Sell the enchanted item and record the sold crafting outcome.
     *
     * @param  Character  $character  The character who crafted the item.
     * @param  Item  $item  The enchanted item.
     * @param  int  $goldCost  The total Gold cost of this attempt.
     * @return BatchCraftingOperationResult The sold operation result.
     */
    private function applySell(Character $character, Item $item, int $goldCost): BatchCraftingOperationResult
    {
        $goldGained = $this->sellForDisplacement($character, $item);

        return BatchCraftingOperationResult::sold($goldCost, $goldGained);
    }

    /**
     * Destroy the enchanted item and record the destroyed crafting outcome.
     *
     * @param  Character  $character  The character who crafted the item.
     * @param  Item  $item  The enchanted item.
     * @param  int  $goldCost  The total Gold cost of this attempt.
     * @return BatchCraftingOperationResult The destroyed operation result.
     */
    private function applyDestroy(Character $character, Item $item, int $goldCost): BatchCraftingOperationResult
    {
        $this->destroyForDisplacement($character, $item);

        return BatchCraftingOperationResult::destroyed($goldCost);
    }

    /**
     * List the enchanted item on the Market and record the listed crafting outcome.
     *
     * @param  Character  $character  The character who crafted the item.
     * @param  Item  $item  The enchanted item.
     * @param  int|null  $listingPrice  The requested Market listing price.
     * @param  int  $goldCost  The total Gold cost of this attempt.
     * @return BatchCraftingOperationResult The listed operation result.
     */
    private function applyList(Character $character, Item $item, ?int $listingPrice, int $goldCost): BatchCraftingOperationResult
    {
        $this->marketBoard->listBatchCraftedItem($character, $item, $listingPrice ?? 0);

        return BatchCraftingOperationResult::listed($goldCost);
    }

    /**
     * Disenchant the enchanted item and record the disenchanted crafting outcome.
     *
     * @param  Character  $character  The character who crafted the item.
     * @param  int  $goldCost  The total Gold cost of this attempt.
     * @return BatchCraftingOperationResult The disenchanted operation result.
     */
    private function applyDisenchant(Character $character, int $goldCost): BatchCraftingOperationResult
    {
        $this->disenchantForDisplacement($character);

        return BatchCraftingOperationResult::disenchanted($goldCost);
    }
}
