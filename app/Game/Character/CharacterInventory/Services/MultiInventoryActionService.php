<?php

namespace App\Game\Character\CharacterInventory\Services;

use App\Flare\Models\Character;
use App\Game\Character\CharacterInventory\Builders\EquipManyBuilder;
use App\Game\Core\Traits\ResponseBuilder;
use Exception;

class MultiInventoryActionService {

    use ResponseBuilder;

    public function __construct(
        private readonly InventorySetService $inventorySetService,
        private readonly  EquipItemService $equipItemService,
        private readonly EquipManyBuilder $equipManyBuilder,
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
}