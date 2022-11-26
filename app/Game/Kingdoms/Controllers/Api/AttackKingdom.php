<?php

namespace App\Game\Kingdoms\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Flare\Models\KingdomLog;
use App\Game\Kingdoms\Requests\AttackRequest;
use App\Game\Kingdoms\Requests\PurchaseMercenaryRequest;
use App\Game\Kingdoms\Requests\DropItemsOnKingdomRequest;
use App\Game\Kingdoms\Service\AttackWithItemsService;
use App\Game\Kingdoms\Service\KingdomAttackService;
use App\Game\Kingdoms\Service\UnitMovementService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;

class AttackKingdom extends Controller {

    /**
     * @var UnitMovementService $unitMovementService
     */
    private UnitMovementService $unitMovementService;

    /**
     * @var AttackWithItemsService $attackWithItemsService
     */
    private AttackWithItemsService $attackWithItemsService;

    /**
     * @var KingdomAttackService $kingdomAttackService
     */
    private KingdomAttackService $kingdomAttackService;

    /**
     * @param UnitMovementService $unitMovementService
     * @param AttackWithItemsService $attackWithItemsService
     * @param KingdomAttackService $kingdomAttackService
     */
    public function __construct(UnitMovementService $unitMovementService,
                                AttackWithItemsService $attackWithItemsService,
                                KingdomAttackService $kingdomAttackService)
    {
        $this->unitMovementService          = $unitMovementService;
        $this->attackWithItemsService       = $attackWithItemsService;
        $this->kingdomAttackService         = $kingdomAttackService;
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

    /**
     * @param DropItemsOnKingdomRequest $request
     * @param Kingdom $kingdom
     * @param Character $character
     * @return JsonResponse
     */
    public function dropItems(DropItemsOnKingdomRequest $request, Kingdom $kingdom, Character $character): JsonResponse {
        $response = $this->attackWithItemsService->useItemsOnKingdom($character, $kingdom, $request->slots);

        $status = $response['status'];

        unset($response['status']);

        return response()->json($response, $status);
    }

    /**
     * @param PurchaseMercenaryRequest $request
     * @param Kingdom $kingdom
     * @param Character $character
     * @return JsonResponse
     */
    public function attackWithUnits(AttackRequest $request, Kingdom $kingdom, Character $character): JsonResponse {
        $response = $this->kingdomAttackService->attackKingdom($character, $kingdom, $request->all());

        $status = $response['status'];

        unset($response['status']);

        return response()->json($response, $status);
    }
}
