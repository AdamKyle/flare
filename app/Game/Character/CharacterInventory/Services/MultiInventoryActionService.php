<?php

namespace App\Game\Character\CharacterInventory\Services;


use Exception;
use Facades\App\Flare\Calculators\SellItemCalculator;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;
use App\Flare\Models\Character;
use App\Flare\Models\InventorySlot;
use App\Game\Character\Builders\AttackBuilders\Handler\UpdateCharacterAttackTypesHandler;
use App\Game\Character\CharacterInventory\Builders\EquipManyBuilder;
use App\Game\Character\CharacterInventory\Jobs\DisenchantMany;
use App\Game\Core\Events\UpdateCharacterInventoryCountEvent;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Shop\Events\SellItemEvent;
use App\Game\Shop\Services\ShopService;
use App\Game\Skills\Services\DisenchantService;

class MultiInventoryActionService
{
    use ResponseBuilder;

    public function __construct(
        private readonly InventorySetService $inventorySetService,
        private readonly EquipItemService $equipItemService,
        private readonly EquipManyBuilder $equipManyBuilder,
        private readonly ShopService $shopService,
        private readonly DisenchantService $disenchantService,
        private readonly CharacterInventoryService $characterInventoryService,
        private readonly UpdateCharacterAttackTypesHandler $updateCharacterAttackTypesHandler,
    ) {}

    public function moveManyItemsToSelectedSet(Character $character, int $setId, array $slotIds): array
    {

        $result = [];

        $lastIndex = count($slotIds) - 1;

        foreach ($slotIds as $index => $slotId) {

            $isLast = false;

            if ($index === $lastIndex) {
                $isLast = true;
            }

            $result = $this->inventorySetService->moveItemToSet($character, $slotId, $setId, false, $isLast);

            if (is_null($result)) {
                continue;
            }

            if ($result['status'] === 422) {
                return $result;
            }
        }

        return $this->successResult([
            'message' => 'Moved all selected items to: ' . $result['moved_to_set_name'] . '.',
            'inventory' => $result['inventory'],
        ]);
    }

    public function equipManyItems(Character $character, array $slotIds): array
    {

        try {
            $itemsToEquip = $this->equipManyBuilder->buildEquipmentArray($character, $slotIds);
        } catch (Exception $e) {
            return $this->errorResult($e->getMessage());
        }

        foreach ($itemsToEquip as $toEquipItem) {
            $this->equipItem($character, $toEquipItem);
        }

        $character = $character->refresh();

        $this->updateCharacterAttackTypesHandler->updateCache($character);

        event(new UpdateCharacterInventoryCountEvent($character));

        $characterInventoryService = $this->characterInventoryService->setCharacter($character);

        return $this->successResult([
            'message' => 'Equipped valid items to your character.',
            'inventory' => $characterInventoryService->getInventoryForApi()
        ]);
    }

    public function sellManyItems(Character $character, array $slotIds): array
    {

        $slots = $character->inventory->slots()
            ->whereIn('id', $slotIds)
            ->whereHas('item', function ($query) {
                return $query->whereNotIn('type', ['alchemy', 'quest', 'artifact', 'trinket']);
            })
            ->where('equipped', false)
            ->get();

        $totalSoldFor = 0;

        foreach ($slots as $slot) {
            $totalSoldFor += $this->sellItem($character, $slot);
        }

        $character = $character->refresh();

        event(new UpdateCharacterInventoryCountEvent($character));

        return $this->successResult([
            'message' => 'Sold all items for: ' . number_format($totalSoldFor) . ' Gold (Minus 5% on each sale), With the exception of Trinkets and Artifacts to the shop. Check your server messages (below - select Server Messsages tab, or for mobile select Server Messages from the dropw down) for details!',
            'inventory' => $this->characterInventoryService->setCharacter($character)->getInventoryForApi(),
        ]);
    }

    public function disenchantManyItems(Character $character, array $slotIds): array
    {
        $filteredSlots = $character->inventory->slots
            ->whereIn('id', $slotIds)
            ->whereNotIn('item.type', ['alchemy', 'quest', 'trinket', 'artifact'])
            ->where('equipped', false)
            ->filter(function ($slot) {
                return !is_null($slot->item->item_prefix_id) || !is_null($slot->item->item_suffix_id);
            });

        $itemIdsToDisenchant = $filteredSlots->pluck('item_id')->toArray();
        $filteredSlotIds = $filteredSlots->pluck('id')->toArray();

        $character->inventory->slots()->whereIn('id', $filteredSlotIds)->delete();

        $character = $character->refresh();

        DisenchantMany::dispatch($character, $itemIdsToDisenchant);

        return $this->successResult([
            'message' => 'Items are queued for disenchanting. Check Server Messages
            (Scroll down for desktop, click Serve Messages tab). If on mobile scroll down,
            selected Server Messages from the Orange Chat Dropdown.',
            'inventory' => $this->characterInventoryService->setCharacter($character)->getInventoryForApi(),
        ]);
    }

    public function destroyManyItems(Character $character, array $slotIds): array
    {

        $character->inventory->slots()
            ->whereIn('id', $slotIds)
            ->whereHas('item', function ($query) {
                return $query->whereNotIn('type', ['alchemy', 'quest', 'artifact']);
            })
            ->where('equipped', false)
            ->delete();

        $character = $character->refresh();

        event(new UpdateCharacterInventoryCountEvent($character));

        return $this->successResult([
            'message' => 'Destroyed all selected selected items (with exception of artifacts. You must manually delete these powerful items. Click the name, click delete and confirm you want to do this, if you have the item.)',
            'inventory' => $this->characterInventoryService->setCharacter($character)->getInventoryForApi(),
        ]);
    }

    private function equipItem(Character $character, array $equipParams): void
    {
        $this->equipItemService->setRequest($equipParams)
            ->setCharacter($character)
            ->replaceItem();
    }

    private function sellItem(Character $character, InventorySlot $slot): int
    {
        $item = $slot->item;

        $totalSoldFor = SellItemCalculator::fetchSalePriceWithAffixes($item);

        event(new SellItemEvent($slot, $character));

        ServerMessageHandler::sendBasicMessage($character->user, 'Sold item: ' . $item->affix_name . ' for: ' . number_format($totalSoldFor) . ' (Minus 5% tax) Gold! (Selling to a shop can never go above 2 billion gold for an individual item)');

        return $totalSoldFor;
    }
}
