<?php

namespace App\Game\Character\CharacterInventory\Services;

use App\Flare\Models\Character;
use App\Game\Core\Traits\ResponseBuilder;

class MultiInventoryActionService {

    use ResponseBuilder;

    public function __construct(private readonly InventorySetService $inventorySetService) {
    }

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
}
