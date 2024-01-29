<?php

namespace App\Game\Kingdoms\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\KingdomBuilding;
use App\Game\Kingdoms\Service\ExpandResourceBuildingService;
use App\Http\Controllers\Controller;

class ResourceBuildingExpansionController extends Controller {

    private ExpandResourceBuildingService $expandResourceBuildingService;

    public function __construct(ExpandResourceBuildingService $expandResourceBuildingService){

        $this->expandResourceBuildingService = $expandResourceBuildingService;
    }

    public function getBuildingExpansionDetails(KingdomBuilding $kingdomBuilding, Character $character) {

    }

    public function expandBuilding(KingdomBuilding $kingdomBuilding, Character $character) {
        $response = $this->expandResourceBuildingService->startExpansion($character, $kingdomBuilding);

        $status = $response['status'];
        unset($response['status']);

        return response()->json($response, $status);
    }

    public function cancelExpansionBuilding(KingdomBuilding $kingdomBuilding, Character $character) {

    }

}
