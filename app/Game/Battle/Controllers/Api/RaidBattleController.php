<?php

namespace App\Game\Battle\Controllers\Api;

use App\Flare\Models\CelestialFight;
use App\Flare\Models\CharacterInCelestialFight;
use App\Flare\Models\Npc;
use App\Flare\Values\NpcTypes;
use App\Game\Battle\Request\CelestialFightRequest;
use App\Game\Battle\Request\ConjureRequest;
use App\Game\Battle\Request\PvpFight;
use App\Game\Battle\Request\PvpFightInfo;
use App\Game\Battle\Services\CelestialFightService;
use App\Game\Battle\Services\PvpService;
use App\Game\Battle\Values\CelestialConjureType;
use App\Game\Messages\Builders\NpcServerMessageBuilder;
use App\Http\Controllers\Controller;
use App\Game\Battle\Services\ConjureService;
use App\Flare\Models\Character;
use App\Flare\Models\Monster;
use App\Game\Messages\Events\ServerMessageEvent;

class RaidBattleController extends Controller {

    public function __construct() {
    }

}
