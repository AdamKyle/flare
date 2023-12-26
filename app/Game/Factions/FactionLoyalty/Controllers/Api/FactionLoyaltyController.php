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
        return response()->json($this->factionLoyaltyService->getLoyaltyInfoForPlane($character));
    }

    public function pledgeLoyalty(Character $character, Faction $faction): JsonResponse {
        return response()->json($this->factionLoyaltyService->pledgeLoyalty($character, $faction));
    }
}
