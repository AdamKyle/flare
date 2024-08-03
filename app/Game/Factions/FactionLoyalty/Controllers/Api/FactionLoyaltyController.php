<?php

namespace App\Game\Factions\FactionLoyalty\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\Faction;
use App\Flare\Models\FactionLoyaltyNpc;
use App\Game\Factions\FactionLoyalty\Services\FactionLoyaltyService;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;

class FactionLoyaltyController extends Controller
{
    private FactionLoyaltyService $factionLoyaltyService;

    public function __construct(FactionLoyaltyService $factionLoyaltyService)
    {
        $this->factionLoyaltyService = $factionLoyaltyService;
    }

    public function fetchLoyaltyInfo(Character $character): JsonResponse
    {

        $response = $this->factionLoyaltyService->getLoyaltyInfoForPlane($character);

        $status = $response['status'];
        unset($response['status']);

        return response()->json($response, $status);
    }

    /**
     * @throws Exception
     */
    public function pledgeLoyalty(Character $character, Faction $faction): JsonResponse
    {

        $response = $this->factionLoyaltyService->pledgeLoyalty($character, $faction);

        $status = $response['status'];
        unset($response['status']);

        return response()->json($response, $status);
    }

    public function removePledge(Character $character, Faction $faction): JsonResponse
    {

        $response = $this->factionLoyaltyService->removePledge($character, $faction);

        $status = $response['status'];
        unset($response['status']);

        return response()->json($response, $status);
    }

    public function assistNpc(Character $character, FactionLoyaltyNpc $factionLoyaltyNpc): JsonResponse
    {
        $response = $this->factionLoyaltyService->assistNpc($character, $factionLoyaltyNpc);

        $status = $response['status'];
        unset($response['status']);

        return response()->json($response, $status);
    }

    public function stopAssistingNpc(Character $character, FactionLoyaltyNpc $factionLoyaltyNpc): JsonResponse
    {
        $response = $this->factionLoyaltyService->stopAssistingNpc($character, $factionLoyaltyNpc);

        $status = $response['status'];
        unset($response['status']);

        return response()->json($response, $status);
    }
}
