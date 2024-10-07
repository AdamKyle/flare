<?php

namespace App\Game\Battle\Controllers\Api;

use App\Flare\Models\Character;
use App\Game\Battle\Request\PvpFight;
use App\Game\Battle\Request\PvpFightInfo;
use App\Game\Battle\Services\PvpService;
use App\Http\Controllers\Controller;

class PvpBattleController extends Controller
{
    private PvpService $pvpService;

    public function __construct(PvpService $pvpService)
    {

        $this->pvpService = $pvpService;
    }

    public function getHealth(PvpFightInfo $request, Character $character)
    {
        $defender = Character::find($request->defender_id);

        if (is_null($defender)) {
            return response()->json([
                'message' => 'You cannot attack that.',
            ], 422);
        }

        return response()->json($this->pvpService->getHealthObject($character, $defender));
    }

    public function fightCharacter(PvpFight $request, Character $character)
    {
        $defender = Character::find($request->defender_id);

        if (is_null($defender)) {
            return response()->json([
                'message' => 'You cannot attack that.',
            ], 422);
        }

        if (! $this->pvpService->isDefenderAtPlayersLocation($character, $defender)) {
            return response()->json([
                'message' => 'You swing at nothing. They must have moved.',
            ], 422);
        }

        if ($defender->is_auto_battling) {
            return response()->json([
                'message' => 'The player is to busy to want to engage with you. Move on child!'
            ], 422);
        }

        $this->pvpService->attack($character, $defender, $request->attack_type);

        return response()->json();
    }

    public function revive(Character $character)
    {
        $this->pvpService->battleEventHandler()->processRevive($character);

        $this->pvpService->cache()->updatePlayerHealth($character, $this->pvpService->cache()->getCachedCharacterData($character, 'health'));

        return response()->json();
    }
}
