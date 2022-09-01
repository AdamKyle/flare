<?php

namespace App\Game\Kingdoms\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Game\Kingdoms\Requests\MoveUnitsRequest;
use App\Game\Kingdoms\Service\UnitMovementService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class AttackKingdom extends Controller {

    /**
     * @var UnitMovementService $unitMovementService
     */
    private UnitMovementService $unitMovementService;

    /**
     * @param UnitMovementService $unitMovementService
     */
    public function __construct(UnitMovementService $unitMovementService) {
        $this->unitMovementService = $unitMovementService;
    }

    /**
     * @param Kingdom $kingdom
     * @param Character $character
     * @return JsonResponse
     */
    public function fetchAttackingData(Kingdom $kingdom, Character $character): JsonResponse {
        $kingdomsToSelect = $this->unitMovementService->getKingdomUnitTravelData($character, $kingdom);

        $itemsToUse = $character->inventory->slots->filter(function ($slot) {
            if ($slot->item->damages_kingdoms) {
                return $slot->item;
            }
        });

        return response()->json([
            'kingdoms'     => $kingdomsToSelect,
            'items_to_use' => array_values($itemsToUse->toArray()),
        ]);
    }
}
