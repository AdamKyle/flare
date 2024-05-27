<?php

namespace App\Game\Character\CharacterInventory\Services;

use App\Flare\Models\Character;
use App\Game\Character\CharacterInventory\Builders\EquipManyBuilder;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Shop\Services\ShopService;
use Exception;

class MultiInventoryActionService {

    use ResponseBuilder;

    public function __construct(
        private readonly InventorySetService $inventorySetService,
        private readonly EquipItemService $equipItemService,
        private readonly EquipManyBuilder $equipManyBuilder,
        private readonly ShopService $shopService
    ) {}

    /**
     * @param Character $character
     * @param int $setId
     * @param array $slotIds
     * @return array
     */
    public function moveManyItemsToSelectedSet(Character $character, int $setId, array $slotIds): array {

        $result = [];

        foreach ($slotIds as $slotId) {
            $result = $this->inventorySetService->moveItemToSet($character, $slotId, $setId);

            if ($result['status'] === 422) {
                return $result;
            }
        }

        if (empty($result)) {
            return $this->errorResult('Nothing was moved, nothing selected.');
        }

        return $this->successResult([
            'message' => 'Moved all selected items to: '.$result['moved_to_set_name'].'.',
            'inventory' => $result['inventory'],
        ]);
    }

    public function equipManyItems(Character $character, array $slotIds): array {

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
                'slot_id'  => $toEquipItem['slot_id'],
                'equip_type' => $toEquipItem['type'],
            ]);

            if ($result['status'] === 422) {
                return $result;
            }
        }

        return $result;
    }

    public function sellManyItems(Character $character, array $slotIds): array {

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

        return $this->successResult([
            'message' => 'Sold the following items: ' . $names . ' for a total of: ' . number_format($soldFor) . ' Gold. (After 5% tax is taken)',
            'inventory' => $result['inventory'],
        ]);
    }

}
