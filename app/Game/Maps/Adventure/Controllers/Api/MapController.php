<?php

namespace App\Game\Maps\Adventure\Controllers\Api;

use Illuminate\Http\Request;
use League\Fractal\Resource\Item;
use League\Fractal\Manager;
use App\Http\Controllers\Controller;
use App\Flare\Events\ServerMessageEvent;
use App\Flare\Models\Character;
use App\Flare\Models\Monster;
use App\Flare\Transformers\CharacterAttackTransformer;
use App\Game\Battle\Events\UpdateCharacterEvent;
use App\Game\Battle\Events\DropsCheckEvent;
use App\Game\Battle\Events\GoldRushCheckEvent;
use App\Game\Battle\Events\AttackTimeOutEvent;
use App\Game\Battle\Jobs\AttackTimeOut;
use App\User;

class MapController extends Controller {

    public function __construct() {
        $this->middleware('auth:api');
    }

    public function index() {
        return response()->json([
            'map_url' => asset('/storage/surface.png'),
        ]);
    }
}
