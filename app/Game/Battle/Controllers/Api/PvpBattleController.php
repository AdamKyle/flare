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

class PvpBattleController extends Controller {

    private PvpService $pvpService;

    public function __construct(PvpService $pvpService) {

        $this->pvpService = $pvpService;
    }

    public function getHealth(PvpFightInfo $request, Character $character) {
        $defender = Character::find($request->defender_id);

        if (is_null($defender)) {
            return response()->json([
                'message' => 'You cannot attack that.'
            ], 422);
        }

        return response()->json($this->pvpService->getHealthObject($character, $defender));
    }

    public function fightCharacter(PvpFight $request, Character $character) {
        $defender = Character::find($request->defender_id);

        if (is_null($defender)) {
            return response()->json([
                'message' => 'You cannot attack that.'
            ], 422);
        }

        if (!$this->pvpService->isDefenderAtPlayersLocation($character, $defender)) {
            return response()->json([
                'message' => 'You swing at nothing. They must have moved.'
            ], 422);
        }

        $this->pvpService->attack($character, $defender, $request->attack_type);

        return response()->json();
    }
}
