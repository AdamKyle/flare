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

    public function sellManyItems(Character $character, array $params): array
    {
        $slotsQuery = $character->inventory->slots()
            ->whereHas('item', static function ($query) {
                $query->whereNotIn('type', ['alchemy', 'quest', 'artifact', 'trinket']);
            })
            ->where('equipped', false);

        if (isset($params['exclude'])) {
            $excludeIds = array_map(static fn ($id): int => (int) $id, (array) $params['exclude']);
            $slotsQuery->whereNotIn('item_id', $excludeIds);
        } elseif (isset($params['ids'])) {
            $includeIds = array_map(static fn ($id): int => (int) $id, (array) $params['ids']);
            $slotsQuery->whereIn('item_id', $includeIds);
        }

        $slots = $slotsQuery->get();

        $totalSoldFor = 0;

        foreach ($slots as $slot) {
            $totalSoldFor += $this->sellItem($character, $slot);
        }

        return $this->successResult([
            'message' => 'Sold all items for: ' . number_format($totalSoldFor) . ' Gold (Minus 5% on each sale)',
        ]);
    }


    public function disenchantManyItems(Character $character, array $slotIds): array
    {
        $slotsQuery = $character->inventory->slots()
            ->whereHas('item', static function ($query) {
                $query->whereNotIn('type', ['alchemy', 'quest', 'artifact', 'trinket']);
            })
            ->where('equipped', false);

        if (isset($params['exclude'])) {
            $excludeIds = array_map(static fn ($id): int => (int) $id, (array) $params['exclude']);
            $slotsQuery->whereNotIn('item_id', $excludeIds);
        } elseif (isset($params['ids'])) {
            $includeIds = array_map(static fn ($id): int => (int) $id, (array) $params['ids']);
            $slotsQuery->whereIn('item_id', $includeIds);
        }

        $itemIdsToDisenchant = $slotsQuery->pluck('item_id')->toArray();
        $filteredSlotIds = $slotIds->pluck('id')->toArray();

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

    public function destroyManyItems(Character $character, array $params): array
    {

        $slotsQuery = $character->inventory->slots()
            ->whereHas('item', static function ($query) {
                $query->whereNotIn('type', ['alchemy', 'quest', 'artifact', 'trinket']);
            })
            ->where('equipped', false);

        if (isset($params['exclude'])) {
            $excludeIds = $params['exclude'];
            $slotsQuery->whereNotIn('item_id', $excludeIds);
        } elseif (isset($params['ids'])) {
            $includeIds = $params['ids'];
            $slotsQuery->whereIn('item_id', $includeIds);
        }

        $slotsQuery->delete();

        return $this->successResult([
            'message' => 'Destroyed all selected selected items (with exception of artifacts. You must manually delete these powerful items. Click the item, click delete and confirm you want to do this, if you have the item.)',
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
