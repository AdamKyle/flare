<?php

namespace App\Game\Battle\Controllers\Api;

use App\Flare\Models\Location;
use App\Flare\Services\BuildMonsterCacheService;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Battle\Jobs\BattleAttackHandler;
use App\Game\Battle\Events\AttackTimeOutEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\Controller;
use App\Game\Battle\Handlers\BattleEventHandler;
use App\Flare\Models\Character;

class BattleController extends Controller {

    /**
     * @var BattleEventHandler $battleEventHandler
     */
    private BattleEventHandler $battleEventHandler;

    /**
     * @param BattleEventHandler $battleEventHandler
     */
    public function __construct(BattleEventHandler $battleEventHandler) {
        $this->middleware('is.character.dead')->except(['revive', 'index']);

        $this->battleEventHandler = $battleEventHandler;
    }

    /**
     * @param Character $character
     * @return JsonResponse
     */
    public function index(Character $character): JsonResponse {
        $characterMap       = $character->map;

        $locationWithEffect = Location::whereNotNull('enemy_strength_type')
                                      ->where('x', $characterMap->character_position_x)
                                      ->where('y', $characterMap->character_position_y)
                                      ->where('game_map_id', $characterMap->game_map_id)
                                      ->first();

        if (!Cache::has('monsters')) {
            resolve(BuildMonsterCacheService::class)->buildCache();
        }

        if (!is_null($locationWithEffect)) {
            $monsters = Cache::get('monsters')[$locationWithEffect->name];
        } else {
            $monsters = Cache::get('monsters')[$character->map->gameMap->name];
        }

        event(new UpdateCharacterStatus($character));

        return response()->json([
            'monsters'  => $monsters,
        ]);
    }

    /**
     * @param Request $request
     * @param Character $character
     * @return JsonResponse
     */
    public function battleResults(Request $request, Character $character): JsonResponse {
        if (!$character->can_attack) {
            return response()->json(['message' => 'invalid input.'], 429);
        }

        if ($request->is_character_dead) {

            $this->battleEventHandler->processDeadCharacter($character);

            return response()->json([
                'time_out' => 20,
            ]);
        }

        if ($request->is_defender_dead) {

            event(new AttackTimeOutEvent($character));

            BattleAttackHandler::dispatch($character->id, $request->monster_id)->onQueue('default_long');
        }

        return response()->json();
    }

    /**
     * @param Character $character
     * @return JsonResponse
     */
    public function revive(Character $character): JsonResponse {
        $this->battleEventHandler->processRevive($character);

        return response()->json([]);
    }

}
