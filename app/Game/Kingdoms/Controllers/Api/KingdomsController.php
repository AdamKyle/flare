<?php

namespace App\Game\Kingdoms\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Game\Kingdoms\Requests\KingdomsLocationRequest;
use App\Game\Kingdoms\Requests\KingdomsSettleRequest;
use App\Http\Controllers\Controller;

class KingdomsController extends Controller {

    public function __construct() {
        $this->middleware('auth:api');
        $this->middleware('is.character.dead');;
    }

    public function getLocationData(KingdomsLocationRequest $request) {
        return response()->json(
            Kingdom::where('x_position', $request->xPosition)->where('y_position', $request->yPosition)->first(), 
            200
        );
    }

    public function settle(KingdomsSettleRequest $request, Character $character) {
        dd($request->all());
        return response()->json([], 200);
    }
}
