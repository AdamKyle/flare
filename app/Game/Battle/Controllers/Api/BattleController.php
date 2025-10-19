<?php

namespace App\Game\Battle\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\Monster;
use App\Game\Battle\Events\AttackTimeOutEvent;
use App\Game\Battle\Handlers\BattleEventHandler;
use App\Game\Battle\Request\AttackTypeRequest;
use App\Game\Battle\Services\MonsterFightService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class BattleController extends Controller
{
    private MonsterFightService $monsterFightService;

    private BattleEventHandler $battleEventHandler;

    public function __construct(MonsterFightService $monsterFightService, BattleEventHandler $battleEventHandler)
    {
        $this->middleware('is.character.dead')->except(['revive', 'index']);

        $this->monsterFightService = $monsterFightService;

        $this->battleEventHandler = $battleEventHandler;
    }

    public function setupMonster(AttackTypeRequest $attackTypeRequest, Character $character, Monster $monster): JsonResponse
    {

        if (! $this->monsterFightService->isAtMonstersLocation($character, $monster->id)) {
            return response()->json([
                'message' => 'You cannot fight a creature of this magnitude with out being at it\'s location.',
            ], 422);
        }

        if (! $this->monsterFightService->isMonsterAlreadyDefeatedThisWeek($character, $monster->id)) {
            return response()->json([
                'message' => 'You already defeated this monster. Reset is on Sundays at 3am America/Edmonton.',
            ], 422);
        }

        $result = $this->monsterFightService->setupMonster($character, [
            'attack_type' => $attackTypeRequest->attack_type,
            'selected_monster_id' => $monster->id,
        ]);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    public function fightMonster(AttackTypeRequest $attackTypeRequest, Character $character): JsonResponse
    {
        $result = $this->monsterFightService->fightMonster($character, $attackTypeRequest->attack_type);

        $status = $result['status'];
        unset($result['status']);

        if ($status !== 200) {
            return response()->json($result, $status);
        }

        if ($result['health']['current_character_health'] <= 0 || $result['health']['current_monster_health'] <= 0) {
            event(new AttackTimeOutEvent($character));
        }

        return response()->json($result, $status);
    }

    public function revive(Character $character): JsonResponse
    {
        $this->battleEventHandler->processRevive($character);

        return response()->json([]);
    }
}
