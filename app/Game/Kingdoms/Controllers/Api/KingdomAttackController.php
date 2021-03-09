<?php

namespace App\Game\Kingdoms\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Flare\Models\Character;
use App\Game\Kingdoms\Requests\SelectedKingdomsRequest;
use App\Game\Kingdoms\Service\KingdomsAttackService;

class KingdomAttackController extends Controller {

    /**
     * @var KingdomsAttackService $kingdomAttackService
     */
    private $kingdomAttackService;

    public function __construct(KingdomsAttackService $kingdomAttackService) {
        $this->middleware('auth:api');
        $this->middleware('is.character.dead');

        $this->kingdomAttackService = $kingdomAttackService;
    }

    public function selectKingdoms(SelectedKingdomsRequest $request, Character $character) {
        $response = $this->kingdomAttackService->fetchSelectedKingdomData($character, $request->selected_kingdoms);

        $status = $response['status'];

        unset($response['status']);

        return response()->json($response, $status);
    }
}