<?php

namespace App\Game\Character\CharacterInventory\Services;

use App\Flare\Models\Character;
use App\Game\Character\Builders\AttackBuilders\Handler\UpdateCharacterAttackTypesHandler;
use App\Game\Character\CharacterInventory\Builders\EquipManyBuilder;
use App\Game\Character\CharacterInventory\Jobs\DisenchantMany;
use App\Game\Core\Events\UpdateCharacterInventoryCountEvent;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Shop\Services\ShopService;
use App\Game\Skills\Services\DisenchantService;
use Exception;
use Illuminate\Support\Facades\Cache;

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

        $result = $this->errorResult('Nothing happened when trying to sell many items. Did you select anything?');

        $itemNames = [];
        $soldFor = 0;

        foreach ($slotIds as $slotId) {
            $result = $this->shopService->sellSpecificItem($character, $slotId);

            if ($result['status'] === 422) {
                return $result;
            }

            $itemNames[] = $result['item_name'];
            $soldFor += $result['sold_for'];
        }

        $names = implode(', ', $itemNames);

        event(new UpdateCharacterInventoryCountEvent($character));

        return $this->successResult([
            'message' => 'Sold the following items: ' . $names . ' for a total of: ' . number_format($soldFor) . ' Gold. (After 5% tax is taken)',
            'inventory' => $result['inventory'],
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
        $result = $this->errorResult('Nothing happened when trying to destroy many items. Did you select anything?');

        $characterInventoryService = $this->characterInventoryService->setCharacter($character);

        foreach ($slotIds as $slotId) {

            $result = $characterInventoryService->deleteItem($slotId);

            if ($result['status'] === 422) {
                return $result;
            }
        }

        event(new UpdateCharacterInventoryCountEvent($character));

        return $this->successResult([
            'message' => 'Destroyed all selected items.',
            'inventory' => $result['inventory'],
        ]);
    }

    private function equipItem(Character $character, array $equipParams): void
    {
        $this->equipItemService->setRequest($equipParams)
            ->setCharacter($character)
            ->replaceItem();
    }
}
