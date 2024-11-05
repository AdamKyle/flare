<?php

namespace App\Game\Character\CharacterInventory\Services;

use App\Flare\Models\Character;
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

        $result = $this->successResult();

        // Equip items
        foreach ($itemsToEquip as $toEquipItem) {
            $result = $this->equipItemService->equipItem($character, [
                'position' => $toEquipItem['position'],
                'slot_id' => $toEquipItem['slot_id'],
                'equip_type' => $toEquipItem['type'],
            ]);

            if ($result['status'] === 422) {
                return $result;
            }
        }

        $character = $character->refresh();

        event(new UpdateCharacterInventoryCountEvent($character));

        return $result;
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

            $itemNames[] = $result['item_name']; // Collect item names into an array
            $soldFor += $result['sold_for'];
        }

        $names = implode(', ', $itemNames); // Convert array of item names to a string

        event(new UpdateCharacterInventoryCountEvent($character));

        return $this->successResult([
            'message' => 'Sold the following items: ' . $names . ' for a total of: ' . number_format($soldFor) . ' Gold. (After 5% tax is taken)',
            'inventory' => $result['inventory'],
        ]);
    }

    public function disenchantManyItems(Character $character, array $slotIds): array
    {

        Cache::put('character-slots-to-disenchant-' . $character->id, $slotIds);

        DisenchantMany::dispatch($character, $slotIds);

        return $this->successResult([
            'message' => 'Items are queued for disenchanting. Check Server Messages
            (Scroll down for desktop, click Serve Messages tab). If on mobile scroll down,
            selected Server Messages from the Orange Chat Dropdown.'
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
}
