<?php

namespace App\Game\Factions\FactionLoyalty\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\Faction;
use App\Flare\Models\FactionLoyaltyNpc;
use App\Game\Factions\FactionLoyalty\Services\FactionLoyaltyService;
use App\Http\Controllers\Controller;
use Exception;
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
     * @throws Exception
     */
    public function pledgeLoyalty(Character $character, Faction $faction): JsonResponse {

        $response = $this->factionLoyaltyService->pledgeLoyalty($character, $faction);

        $status = $response['status'];
        unset($response['status']);

        return response()->json($response, $status);
    }

    /**
     * @param Character $character
     * @param Faction $faction
     * @return JsonResponse
     */
    public function removePledge(Character $character, Faction $faction): JsonResponse {

        $response = $this->factionLoyaltyService->removePledge($character, $faction);

        $status = $response['status'];
        unset($response['status']);

        return response()->json($response, $status);
    }

    /**
     * @param Character $character
     * @param FactionLoyaltyNpc $factionLoyaltyNpc
     * @return JsonResponse
     */
    public function assistNpc(Character $character, FactionLoyaltyNpc $factionLoyaltyNpc): JsonResponse {
        $response = $this->factionLoyaltyService->assistNpc($character, $factionLoyaltyNpc);

        $status = $response['status'];
        unset($response['status']);

        return response()->json($response, $status);
    }

    /**
     * @param Character $character
     * @param FactionLoyaltyNpc $factionLoyaltyNpc
     * @return JsonResponse
     */
    public function stopAssistingNpc(Character $character, FactionLoyaltyNpc $factionLoyaltyNpc): JsonResponse {
        $response = $this->factionLoyaltyService->stopAssistingNpc($character, $factionLoyaltyNpc);

        $status = $response['status'];
        unset($response['status']);

        return response()->json($response, $status);
    }
}
