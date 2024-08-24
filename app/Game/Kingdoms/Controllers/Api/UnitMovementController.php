<?php

namespace App\Game\Kingdoms\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Flare\Models\UnitMovementQueue;
use App\Game\Kingdoms\Requests\MoveUnitsRequest;
use App\Game\Kingdoms\Service\UnitMovementService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class UnitMovementController extends Controller
{
    private UnitMovementService $unitMovementService;

    public function __construct(UnitMovementService $unitMovementService)
    {
        $this->unitMovementService = $unitMovementService;
    }

    public function fetchAvailableKingdomsAndUnits(Character $character, Kingdom $kingdom): JsonResponse
    {
        return response()->json($this->unitMovementService->getKingdomUnitTravelData($character, $kingdom));
    }

    public function moveUnitsBetweenOwnKingdom(MoveUnitsRequest $request, Character $character, Kingdom $kingdom): JsonResponse
    {
        return response()->json($this->unitMovementService->moveUnitsToKingdom($character, $kingdom, $request->all()));
    }

    public function recallUnits(UnitMovementQueue $unitMovementQueue, Character $character): JsonResponse
    {

        if ($unitMovementQueue->character_id !== $character->id) {
            return response()->json(['message' => 'You cannot do that'], 422);
        }

        return response()->json($this->unitMovementService->recallUnits($unitMovementQueue, $character));
    }
}
