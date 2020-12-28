<?php

namespace App\Game\Kingdoms\Controllers\Api;

use App\Flare\Models\Character;
use App\Game\Kingdoms\Requests\KingdomsSettleRequest;
use App\Http\Controllers\Controller;

class KingdomsController extends Controller {

    public function __construct() {
        $this->middleware('auth:api');
        $this->middleware('is.character.dead');;
    }

    public function settle(KingdomsSettleRequest $request, Character $character) {
        return response()->json([], 200);
    }
}
