<?php

namespace App\Game\Automation\BatchCrafting\Handlers;

use App\Flare\Models\BatchCrafting;
use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Game\Automation\BatchCrafting\Attributes\HandlesBatchCraftingMode;
use App\Game\Automation\BatchCrafting\Contracts\BatchCraftingHandler;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingEndReason;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingOutputDestination;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Enums\CraftingBatchFailureReason;
use App\Game\Automation\BatchCrafting\Enums\CraftingBatchMode;
use App\Game\Automation\BatchCrafting\Values\BatchCraftingOperationResult;
use App\Game\Character\CharacterInventory\Services\BatchCraftingSetService;
use App\Game\Messages\Handlers\ServerMessageHandler;
use App\Game\Skills\Services\CraftingService;
use Closure;
use Facades\App\Game\Core\Items\Pricing\SellItemCalculator;
use RuntimeException;

#[HandlesBatchCraftingMode(BatchCraftingType::CRAFT, CraftingBatchMode::AMOUNT)]
class CraftAmountHandler implements BatchCraftingHandler
{
    /**
     * @param  CraftingService  $craftingService  The domain crafting service.
     * @param  BatchCraftingSetService  $batchCraftingSetService  The Crafted Items Set domain service.
     * @param  ServerMessageHandler  $serverMessageHandler  The player server-message dispatcher.
     */
    public function __construct(
        private readonly CraftingService $craftingService,
        private readonly BatchCraftingSetService $batchCraftingSetService,
        private readonly ServerMessageHandler $serverMessageHandler,
    ) {}

    /**
     * Execute one Craft Amount Batch Crafting operation for the running batch.
     *
     * @param  BatchCrafting  $batchCrafting  The running Batch Crafting record.
     * @param  Character  $character  The character running the batch.
     * @return BatchCraftingOperationResult The outcome of the operation.
     */
    public function handle(BatchCrafting $batchCrafting, Character $character): BatchCraftingOperationResult
    {
        $progress = $batchCrafting->progress;

        if ($this->hasReachedRequestedAmount($progress)) {
            return BatchCraftingOperationResult::ended(BatchCraftingEndReason::AMOUNT_REACHED);
        }

        $item = $this->findCraftableItem($character, $progress);

        if (is_null($item)) {
            return BatchCraftingOperationResult::ended(BatchCraftingEndReason::MAXED_OR_NOTHING_LEFT);
        }

        $craftingType = $progress['specific_crafting_type'];
        $disposition = BatchCraftingDisposition::from($batchCrafting->disposition);

        $placeItem = null;

        if ($disposition === BatchCraftingDisposition::KEEP) {
            $resolvedDestination = $this->resolveRetainedDestination($character, $progress);

            if ($resolvedDestination instanceof BatchCraftingEndReason) {
                return BatchCraftingOperationResult::ended($resolvedDestination);
            }

            $placeItem = $resolvedDestination;
        }

        $result = $this->craftItem($character, $disposition, $item, $craftingType, $placeItem);

        if (! $result->didCraft()) {
            return $result;
        }

        $progress['craft_specific_count']++;
        $batchCrafting->update(['progress' => $progress]);

        return $result;
    }

    /**
     * Determine whether the Craft Amount run has already reached its requested amount.
     *
     * @param  array  $progress  The persisted Craft Amount progress data.
     * @return bool True when the requested amount has been reached.
     */
    private function hasReachedRequestedAmount(array $progress): bool
    {
        return $progress['craft_specific_count'] >= $progress['craft_amount'];
    }

    /**
     * Find the character's craftable item matching the persisted Craft Amount progress.
     *
     * @param  Character  $character  The character running the batch.
     * @param  array  $progress  The persisted Craft Amount progress data.
     * @return Item|null The matching craftable item, or null when unavailable.
     */
    private function findCraftableItem(Character $character, array $progress): ?Item
    {
        return $this->craftingService->fetchCraftableItems($character, ['crafting_type' => $progress['specific_crafting_type']], false)
            ->first(fn (Item $craftableItem): bool => $craftableItem->id === $progress['specific_item_id']);
    }

    /**
     * Craft the item and apply its configured Batch Crafting disposition.
     *
     * @param  Character  $character  The character running the batch.
     * @param  BatchCraftingDisposition  $disposition  The configured crafting disposition.
     * @param  Item  $item  The item to craft.
     * @param  string  $craftingType  The crafting type used to craft the item.
     * @param  Closure|null  $placeItem  The retained-item placement callback, when keeping the item.
     * @return BatchCraftingOperationResult The outcome of the craft attempt.
     */
    private function craftItem(Character $character, BatchCraftingDisposition $disposition, Item $item, string $craftingType, ?Closure $placeItem): BatchCraftingOperationResult
    {
        try {
            $craftResult = $disposition === BatchCraftingDisposition::KEEP
                ? $this->craftingService->craftForBatch($character, $item, $craftingType, true, $placeItem)
                : $this->craftingService->craftForBatch($character, $item, $craftingType);
        } catch (RuntimeException) {
            return BatchCraftingOperationResult::ended(BatchCraftingEndReason::FAILED);
        }

        if (! $craftResult['success']) {
            return $this->translateFailure($craftResult['reason']);
        }

        $craftedItem = $craftResult['item'];

        return match ($disposition) {
            BatchCraftingDisposition::KEEP => $this->applyKeep($character, $craftedItem, $craftResult['destination']),
            BatchCraftingDisposition::SELL => $this->applySell($character, $craftedItem),
            BatchCraftingDisposition::DESTROY => $this->applyDestroy($character, $craftedItem),
        };
    }

    /**
     * Translate a CraftingService failure reason into a Batch Crafting operation result.
     *
     * @param  string  $reason  The CraftingService failure reason value.
     * @return BatchCraftingOperationResult The translated operation result.
     */
    private function translateFailure(string $reason): BatchCraftingOperationResult
    {
        return match (CraftingBatchFailureReason::from($reason)) {
            CraftingBatchFailureReason::NOT_ENOUGH_GOLD => BatchCraftingOperationResult::ended(BatchCraftingEndReason::NO_GOLD),
            CraftingBatchFailureReason::SKILL_TOO_LOW => BatchCraftingOperationResult::ended(BatchCraftingEndReason::MAXED_OR_NOTHING_LEFT),
            CraftingBatchFailureReason::FAILED_ROLL => BatchCraftingOperationResult::failed(),
        };
    }

    /**
     * Send the Keep server messages and record the kept crafting outcome.
     *
     * @param  Character  $character  The character who crafted the item.
     * @param  Item  $item  The crafted item.
     * @param  array{destination: string, id: int}  $destination  The resolved retained-item destination.
     * @return BatchCraftingOperationResult The kept operation result.
     */
    private function applyKeep(Character $character, Item $item, array $destination): BatchCraftingOperationResult
    {
        $itemName = $item->affix_name ?? $item->name;
        $destinationEnum = BatchCraftingOutputDestination::from($destination['destination']);
        $linkId = $destination['id'];

        $keptMessage = match ($destinationEnum) {
            BatchCraftingOutputDestination::INVENTORY => 'Kept: '.$itemName.' in your Inventory.',
            BatchCraftingOutputDestination::CRAFTED_ITEMS_SET => 'Kept: '.$itemName.' in your Crafted Items Set.',
        };

        $this->serverMessageHandler->sendBasicMessageWithLink($character->user, 'You crafted a: '.$itemName.'!', $linkId, $destinationEnum->value, $itemName);
        $this->serverMessageHandler->sendBasicMessageWithLink($character->user, $keptMessage, $linkId, $destinationEnum->value, $itemName);

        return BatchCraftingOperationResult::kept();
    }

    /**
     * Sell the crafted item and record the sold crafting outcome.
     *
     * @param  Character  $character  The character who crafted the item.
     * @param  Item  $item  The crafted item.
     * @return BatchCraftingOperationResult The sold operation result.
     */
    private function applySell(Character $character, Item $item): BatchCraftingOperationResult
    {
        $itemName = $item->affix_name ?? $item->name;
        $goldGained = max(0, SellItemCalculator::fetchSalePriceWithAffixes($item));

        $character->increment('gold', $goldGained);

        $this->serverMessageHandler->sendBasicMessage($character->user, 'Sold: '.$itemName.' for: '.number_format($goldGained).' Gold.');

        return BatchCraftingOperationResult::sold();
    }

    /**
     * Destroy the crafted item and record the destroyed crafting outcome.
     *
     * @param  Character  $character  The character who crafted the item.
     * @param  Item  $item  The crafted item.
     * @return BatchCraftingOperationResult The destroyed operation result.
     */
    private function applyDestroy(Character $character, Item $item): BatchCraftingOperationResult
    {
        $itemName = $item->affix_name ?? $item->name;

        $this->serverMessageHandler->sendBasicMessage($character->user, 'Destroyed: '.$itemName.'.');

        return BatchCraftingOperationResult::destroyed();
    }

    /**
     * Resolve the retained item's output destination placement callback, or a capacity end reason.
     *
     * @param  Character  $character  The character running the batch.
     * @param  array  $progress  The persisted Craft Amount progress data.
     * @return BatchCraftingEndReason|Closure The capacity end reason, or the placement callback.
     */
    private function resolveRetainedDestination(Character $character, array $progress): BatchCraftingEndReason|Closure
    {
        $destination = BatchCraftingOutputDestination::from($progress['output_destination']);

        return match ($destination) {
            BatchCraftingOutputDestination::INVENTORY => $this->resolveInventoryDestination($character),
            BatchCraftingOutputDestination::CRAFTED_ITEMS_SET => $this->resolveCraftedItemsSetDestination($character),
        };
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

        return function (Item $craftedItem) use ($character): array {
            $inventory = $character->inventory;

            if (is_null($inventory)) {
                throw new RuntimeException('The character no longer has an Inventory to retain the crafted item in.');
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

        return function (Item $craftedItem) use ($character): array {
            $result = $this->batchCraftingSetService->createItemInBatchCraftingSet($character, $craftedItem);

            if (! $result['success'] || is_null($result['set_slot'])) {
                throw new RuntimeException('The Crafted Items Set could not accept the crafted item.');
            }

            return [
                'destination' => BatchCraftingOutputDestination::CRAFTED_ITEMS_SET->value,
                'id' => $result['set_slot']->id,
            ];
        };
    }
}
