<?php

namespace App\Game\Kingdoms\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Game\Kingdoms\Requests\AttackRequest;
use App\Game\Kingdoms\Requests\DropItemsOnKingdomRequest;
use App\Game\Kingdoms\Service\AttackWithItemsService;
use App\Game\Kingdoms\Service\KingdomAttackService;
use App\Game\Kingdoms\Service\UnitMovementService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class AttackKingdom extends Controller
{
    private UnitMovementService $unitMovementService;

    private AttackWithItemsService $attackWithItemsService;

    private KingdomAttackService $kingdomAttackService;

    public function __construct(UnitMovementService $unitMovementService,
        AttackWithItemsService $attackWithItemsService,
        KingdomAttackService $kingdomAttackService)
    {
        $this->unitMovementService = $unitMovementService;
        $this->attackWithItemsService = $attackWithItemsService;
        $this->kingdomAttackService = $kingdomAttackService;
    }

    public function fetchAttackingData(Kingdom $kingdom, Character $character): JsonResponse
    {
        $kingdomsToSelect = $this->unitMovementService->getKingdomUnitTravelData($character, $kingdom);

        $itemsToUse = $character->inventory->slots->filter(function ($slot) {
            if ($slot->item->damages_kingdoms) {
                return $slot->item;
            }
        });

        return response()->json([
            'kingdoms' => $kingdomsToSelect,
            'items_to_use' => array_values($itemsToUse->toArray()),
        ]);
    }

    public function dropItems(DropItemsOnKingdomRequest $request, Kingdom $kingdom, Character $character): JsonResponse
    {
        $response = $this->attackWithItemsService->useItemsOnKingdom($character, $kingdom, $request->slots);

        $status = $response['status'];

        unset($response['status']);

        return response()->json($response, $status);
    }

    public function attackWithUnits(AttackRequest $request, Kingdom $kingdom, Character $character): JsonResponse
    {
        $response = $this->kingdomAttackService->attackKingdom($character, $kingdom, $request->all());

        $status = $response['status'];

        unset($response['status']);

        return response()->json($response, $status);
    }
}
