<?php

namespace App\Game\Factions\FactionLoyalty\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\Faction;
use App\Game\Factions\FactionLoyalty\Services\FactionLoyaltyService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class FactionLoyaltyController extends Controller {

    /**
     * @var FactionLoyaltyService $factionLoyaltyService
     */
    private FactionLoyaltyService $factionLoyaltyService;

    /**
     * @param FactionLoyaltyService $factionLoyaltyService
     */
    public function __construct(FactionLoyaltyService $factionLoyaltyService) {
        $this->factionLoyaltyService = $factionLoyaltyService;
    }

    /**
     * @param Character $character
     * @return JsonResponse
     */
    public function fetchLoyaltyInfo(Character $character): JsonResponse {

        $response = $this->factionLoyaltyService->getLoyaltyInfoForPlane($character);

        $status = $response['status'];
        unset($response['status']);

        return response()->json($response, $status);
    }

    /**
     * @param Character $character
     * @param Faction $faction
     * @return JsonResponse
     */
    public function pledgeLoyalty(Character $character, Faction $faction): JsonResponse {

        $response = $this->factionLoyaltyService->pledgeLoyalty($character);

        $status = $response['status'];
        unset($response['status']);

        return response()->json($response, $status);
    }

    /**
     * @param Charactrer $character
     * @param Faction $faction
     * @return JsonResponse
     */
    public function removePledge(Charactrer $character, Faction $faction): JsonResponse {

        $response = $this->factionLoyaltyService->removePledge($character);

        $status = $response['status'];
        unset($response['status']);

        return response()->json($response, $status);
    }
}
