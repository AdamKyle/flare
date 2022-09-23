<?php

namespace App\Game\Kingdoms\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Flare\Models\UnitMovementQueue;
use App\Game\Kingdoms\Requests\MoveUnitsRequest;
use App\Game\Kingdoms\Requests\RecallUnitsRequest;
use App\Game\Kingdoms\Service\UnitMovementService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class UnitMovementController extends Controller {

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
     * @param Character $character
     * @param Kingdom $kingdom
     * @return JsonResponse
     */
    public function fetchAvailableKingdomsAndUnits(Character $character, Kingdom $kingdom): JsonResponse {
        return response()->json($this->unitMovementService->getKingdomUnitTravelData($character, $kingdom));
    }

    /**
     * @param MoveUnitsRequest $request
     * @param Character $character
     * @param Kingdom $kingdom
     * @return JsonResponse
     */
    public function moveUnitsBetweenOwnKingdom(MoveUnitsRequest $request, Character $character, Kingdom $kingdom): JsonResponse {
        return response()->json($this->unitMovementService->moveUnitsToKingdom($character, $kingdom, $request->all()));
    }

    /**
     * @param UnitMovementQueue $unitMovementQueue
     * @param Character $character
     * @return JsonResponse
     */
    public function recallUnits(UnitMovementQueue $unitMovementQueue, Character $character): JsonResponse {

        if ($unitMovementQueue->character_id !== $character->id) {
            return response()->json(['message' => 'You cannot do that'], 422);
        }

        return response()->json($this->unitMovementService->recallUnits($unitMovementQueue, $character));
    }
}
