<?php

namespace App\Game\Kingdoms\Controllers\Api;

use App\Flare\Models\Character;
use App\Game\Kingdoms\Traits\UnitInMovementFormatter;
use App\Http\Controllers\Controller;

class KingdomUnitMovementController extends Controller {

    use UnitInMovementFormatter;

    public function __construct() {
        $this->middleware('auth:api');
    }

    public function fetchUnitMovement(Character $character) {
        $unitsInMovement = $character->unitMovementQueues()->where('is_moving', true)->get();


        return response()->json($this->format($unitsInMovement), 200);
    }


}
