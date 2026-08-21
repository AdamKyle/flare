<?php

namespace App\Game\Automation\BatchCrafting\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingEndReason;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingOutputDestination;
use App\Game\Automation\BatchCrafting\Enums\CraftingBatchFailureReason;
use App\Game\Automation\BatchCrafting\Values\BatchCraftingOperationResult;
use App\Game\Character\CharacterInventory\Services\BatchCraftingSetService;
use App\Game\Character\CharacterInventory\Services\CharacterInventoryService;
use App\Game\Character\CharacterInventory\Services\InventorySetService;
use App\Game\Messages\Handlers\ServerMessageHandler;
use App\Game\Skills\Services\CraftingService;
use App\Game\Skills\Values\CraftingMessageMode;
use Closure;
use Facades\App\Game\Core\Items\Pricing\SellItemCalculator;

class CraftingBatchAttemptService
{
    /**
     * @param  CraftingService  $craftingService
     * @param  BatchCraftingSetService  $batchCraftingSetService
     * @param  CharacterInventoryService  $characterInventoryService
     * @param  InventorySetService  $inventorySetService
     * @param  ServerMessageHandler  $serverMessageHandler
     */
    public function __construct(
        private readonly CraftingService $craftingService,
        private readonly BatchCraftingSetService $batchCraftingSetService,
        private readonly CharacterInventoryService $characterInventoryService,
        private readonly InventorySetService $inventorySetService,
        private readonly ServerMessageHandler $serverMessageHandler,
    ) {}

    /**
     * Return the character's current class-adjusted Gold cost to craft the item.
     *
     * @param  Character  $character  The character crafting the item.
     * @param  Item  $item  The item being crafted.
     * @return int The Gold cost to craft the item.
     */
    public function goldCostFor(Character $character, Item $item): int
    {
        return $this->craftingService->getItemCostForAutomation($character, $item);
    }

    /**
     * Attempt to craft the item and apply the requested Batch Crafting disposition to the outcome.
     *
     * @param  Character  $character  The character running the batch.
     * @param  BatchCraftingDisposition  $disposition  The configured crafting disposition.
     * @param  Item  $item  The item to craft.
     * @param  string  $craftingType  The crafting type used to craft the item.
     * @param  int  $goldCost  The Gold cost of this attempt.
     * @param  Closure|null  $placeItem  The retained-item placement callback, when keeping the item.
     * @return BatchCraftingOperationResult The outcome of the craft attempt.
     */
    public function attempt(Character $character, BatchCraftingDisposition $disposition, Item $item, string $craftingType, int $goldCost, ?Closure $placeItem): BatchCraftingOperationResult
    {
        $craftResult = $this->craftingService->craftForBatch($character, $item, $craftingType, CraftingMessageMode::BATCH_CRAFTING, $placeItem);

        if (! $craftResult['success']) {
            return $this->translateFailure($craftResult['reason'], $goldCost);
        }

        $craftedItem = $craftResult['item'];

        $result = match ($disposition) {
            BatchCraftingDisposition::KEEP, BatchCraftingDisposition::KEEP_BEST_SELL_REST, BatchCraftingDisposition::KEEP_BEST_DESTROY_REST => $this->applyKeep($character, $craftedItem, $craftResult['destination'], $goldCost),
            BatchCraftingDisposition::SELL => $this->applySell($character, $craftedItem, $goldCost),
            BatchCraftingDisposition::DESTROY => $this->applyDestroy($character, $craftedItem, $goldCost),
        };

        return $result->withXpGained($craftResult['xp_gained']);
    }

    /**
     * Resolve the retained item's output destination placement callback, or a capacity end reason.
     *
     * @param  Character  $character  The character running the batch.
     * @param  string  $outputDestination  The requested output destination value.
     * @param  int|null  $outputSetId  The selected target Inventory Set id, required only for the Inventory Set destination.
     * @return BatchCraftingEndReason|Closure The capacity end reason, or the placement callback.
     */
    public function resolveRetainedDestination(Character $character, string $outputDestination, ?int $outputSetId = null): BatchCraftingEndReason|Closure
    {
        $destination = BatchCraftingOutputDestination::from($outputDestination);

        return match ($destination) {
            BatchCraftingOutputDestination::INVENTORY => $this->resolveInventoryDestination($character),
            BatchCraftingOutputDestination::CRAFTED_ITEMS_SET => $this->resolveCraftedItemsSetDestination($character),
            BatchCraftingOutputDestination::INVENTORY_SET => $this->resolveInventorySetDestination($character, $outputSetId),
        };
    }

    /**
     * Sell the crafted item for Gold on behalf of a disposition that discards a displaced item.
     *
     * @param  Character  $character  The character who crafted the item.
     * @param  Item  $item  The item being sold.
     * @return int The Gold gained from the sale.
     */
    public function sellForDisplacement(Character $character, Item $item): int
    {
        $itemName = $item->affix_name ?? $item->name;
        $goldGained = max(0, SellItemCalculator::fetchSalePriceWithAffixes($item));

        $character->increment('gold', $goldGained);

        $this->serverMessageHandler->sendBasicMessage($character->user, 'Sold: '.$itemName.' for: '.number_format($goldGained).' Gold.');

        return $goldGained;
    }

    /**
     * Destroy a displaced item on behalf of a disposition that discards it.
     *
     * @param  Character  $character  The character who crafted the item.
     * @param  Item  $item  The item being destroyed.
     * @return void This method does not return a value.
     */
    public function destroyForDisplacement(Character $character, Item $item): void
    {
        $itemName = $item->affix_name ?? $item->name;

        $this->serverMessageHandler->sendBasicMessage($character->user, 'Destroyed: '.$itemName.'.');
    }

    /**
     * Translate a CraftingService failure reason into a Batch Crafting operation result.
     *
     * @param  string  $reason  The CraftingService failure reason value.
     * @param  int  $goldCost  The Gold cost of the attempt.
     * @return BatchCraftingOperationResult The translated operation result.
     */
    public function translateFailure(string $reason, int $goldCost): BatchCraftingOperationResult
    {
        return match (CraftingBatchFailureReason::from($reason)) {
            CraftingBatchFailureReason::NOT_ENOUGH_GOLD => BatchCraftingOperationResult::ended(BatchCraftingEndReason::NO_GOLD),
            CraftingBatchFailureReason::SKILL_TOO_LOW => BatchCraftingOperationResult::ended(BatchCraftingEndReason::MAXED_OR_NOTHING_LEFT),
            CraftingBatchFailureReason::FAILED_ROLL => BatchCraftingOperationResult::failed($goldCost),
            CraftingBatchFailureReason::DESTINATION_FAILED => BatchCraftingOperationResult::failedAndEnded(BatchCraftingEndReason::FAILED, $goldCost),
        };
    }

    /**
     * Send the Keep server message and record the kept crafting outcome.
     *
     * @param  Character  $character  The character who crafted the item.
     * @param  Item  $item  The crafted item.
     * @param  array{destination: string, id: int}  $destination  The resolved retained-item destination.
     * @param  int  $goldCost  The Gold cost of the attempt.
     * @return BatchCraftingOperationResult The kept operation result.
     */
    private function applyKeep(Character $character, Item $item, array $destination, int $goldCost): BatchCraftingOperationResult
    {
        $itemName = $item->affix_name ?? $item->name;
        $destinationEnum = BatchCraftingOutputDestination::from($destination['destination']);
        $linkId = $destination['id'];

        $keptMessage = match ($destinationEnum) {
            BatchCraftingOutputDestination::INVENTORY => 'Kept: '.$itemName.' in your Inventory.',
            BatchCraftingOutputDestination::CRAFTED_ITEMS_SET => 'Kept: '.$itemName.' in your Crafted Items Set.',
            BatchCraftingOutputDestination::INVENTORY_SET => 'Kept: '.$itemName.' in your Set.',
        };

        $this->serverMessageHandler->sendBasicMessageWithLink($character->user, $keptMessage, $linkId, $destinationEnum->value, $itemName);

        return BatchCraftingOperationResult::kept($goldCost);
    }

    /**
     * Sell the crafted item and record the sold crafting outcome.
     *
     * @param  Character  $character  The character who crafted the item.
     * @param  Item  $item  The crafted item.
     * @param  int  $goldCost  The Gold cost of the attempt.
     * @return BatchCraftingOperationResult The sold operation result.
     */
    private function applySell(Character $character, Item $item, int $goldCost): BatchCraftingOperationResult
    {
        $goldGained = $this->sellForDisplacement($character, $item);

        return BatchCraftingOperationResult::sold($goldCost, $goldGained);
    }

    /**
     * Destroy the crafted item and record the destroyed crafting outcome.
     *
     * @param  Character  $character  The character who crafted the item.
     * @param  Item  $item  The crafted item.
     * @param  int  $goldCost  The Gold cost of the attempt.
     * @return BatchCraftingOperationResult The destroyed operation result.
     */
    private function applyDestroy(Character $character, Item $item, int $goldCost): BatchCraftingOperationResult
    {
        $this->destroyForDisplacement($character, $item);

        return BatchCraftingOperationResult::destroyed($goldCost);
    }

    /**
     * Resolve the Inventory destination placement callback, or a capacity end reason.
     *
     * @param  Character  $character  The character running the batch.
     * @return BatchCraftingEndReason|Closure The capacity end reason, or the placement callback.
     */
    private function resolveInventoryDestination(Character $character): BatchCraftingEndReason|Closure
    {
        if ($character->isInventoryFull()) {
            return BatchCraftingEndReason::NO_INVENTORY_SPACE;
        }

        return function (Item $craftedItem) use ($character): ?array {
            $inventory = $character->inventory;

            if (is_null($inventory)) {
                return null;
            }

            $slot = $inventory->slots()->create([
                'inventory_id' => $inventory->id,
                'item_id' => $craftedItem->id,
            ]);

            return [
                'destination' => BatchCraftingOutputDestination::INVENTORY->value,
                'id' => $slot->id,
            ];
        };
    }

    /**
     * Resolve the Crafted Items Set destination placement callback, or a capacity end reason.
     *
     * @param  Character  $character  The character running the batch.
     * @return BatchCraftingEndReason|Closure The capacity end reason, or the placement callback.
     */
    private function resolveCraftedItemsSetDestination(Character $character): BatchCraftingEndReason|Closure
    {
        if (! $this->batchCraftingSetService->canAccept($character, 1)) {
            return BatchCraftingEndReason::BATCH_CRAFTING_SET_FULL;
        }

        return function (Item $craftedItem) use ($character): ?array {
            $result = $this->batchCraftingSetService->createItemInBatchCraftingSet($character, $craftedItem);

            if (! $result['success'] || is_null($result['set_slot'])) {
                return null;
            }

            return [
                'destination' => BatchCraftingOutputDestination::CRAFTED_ITEMS_SET->value,
                'id' => $result['set_slot']->id,
            ];
        };
    }

    /**
     * Resolve the selected normal Inventory Set destination placement callback, or a capacity end reason.
     *
     * @param  Character  $character  The character running the batch.
     * @param  int|null  $outputSetId  The selected target Inventory Set id.
     * @return BatchCraftingEndReason|Closure The capacity end reason, or the placement callback.
     */
    private function resolveInventorySetDestination(Character $character, ?int $outputSetId): BatchCraftingEndReason|Closure
    {
        $set = is_null($outputSetId)
            ? null
            : $this->characterInventoryService->setCharacter($character)->resolveValidTargetInventorySet($outputSetId);

        if (is_null($set) || $set->remainingSlots() < 1) {
            return BatchCraftingEndReason::CRAFT_SET_FULL;
        }

        return function (Item $craftedItem) use ($set): array {
            $setSlot = $this->inventorySetService->putItemIntoSet($set, $craftedItem);

            return [
                'destination' => BatchCraftingOutputDestination::INVENTORY_SET->value,
                'id' => $setSlot->id,
            ];
        };
    }
}
