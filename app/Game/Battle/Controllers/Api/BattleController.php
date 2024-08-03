<?php

namespace App\Game\Battle\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\Location;
use App\Flare\Models\Monster;
use App\Flare\Services\BuildMonsterCacheService;
use App\Flare\Values\ItemEffectsValue;
use App\Game\Battle\Events\AttackTimeOutEvent;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Battle\Handlers\BattleEventHandler;
use App\Game\Battle\Request\AttackTypeRequest;
use App\Game\Battle\Services\MonsterFightService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

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

    public function index(Character $character): JsonResponse
    {
        $characterMap = $character->map;

        $locationWithEffect = Location::whereNotNull('enemy_strength_type')
            ->where('x', $characterMap->character_position_x)
            ->where('y', $characterMap->character_position_y)
            ->where('game_map_id', $characterMap->game_map_id)
            ->first();

        $locationWithType = Location::whereNotNull('type')
            ->where('x', $characterMap->character_position_x)
            ->where('y', $characterMap->character_position_y)
            ->where('game_map_id', $characterMap->game_map_id)
            ->first();

        if (! Cache::has('monsters')) {
            resolve(BuildMonsterCacheService::class)->buildCache();
        }

        $isTheIcePlane = $character->map->gameMap->mapType()->isTheIcePlane();
        $isDelusionalMemories = $character->map->gameMap->mapType()->isDelusionalMemories();
        $hasPurgatoryAccess = $character->inventory->slots->where('item.effect', ItemEffectsValue::PURGATORY)->count() > 0;
        $monsters = Cache::get('monsters')[$character->map->gameMap->name];

        if (! is_null($locationWithEffect) && ! $isTheIcePlane) {
            $monsters = Cache::get('monsters')[$locationWithEffect->name];
        } elseif (! is_null($locationWithEffect) && $isTheIcePlane) {

            if ($hasPurgatoryAccess) {
                $monsters = Cache::get('monsters')[$locationWithEffect->name];
            } else {
                $monsters = Cache::get('monsters')[$character->map->gameMap->name]['easier'];
            }
        }

        if ($isTheIcePlane && $hasPurgatoryAccess) {
            $monsters = Cache::get('monsters')[$character->map->gameMap->name]['regular'];
        } elseif ($isTheIcePlane && ! $hasPurgatoryAccess) {
            $monsters = Cache::get('monsters')[$character->map->gameMap->name]['easier'];
        }

        if ($isDelusionalMemories && $hasPurgatoryAccess) {
            $monsters = Cache::get('monsters')[$character->map->gameMap->name]['regular'];
        } elseif ($isDelusionalMemories && ! $hasPurgatoryAccess) {
            $monsters = Cache::get('monsters')[$character->map->gameMap->name]['easier'];
        }

        if (! is_null($locationWithType)) {
            $monstersForLocation = Cache::get('special-location-monsters');

            if (isset($monstersForLocation['location-type-'.$locationWithType->type])) {
                $monsters = $monstersForLocation['location-type-'.$locationWithType->type];
            }
        }

        event(new UpdateCharacterStatus($character));

        $monsters = collect($monsters);

        return response()->json([
            'monsters' => $monsters->map(function ($monster) {
                return [
                    'id' => $monster['id'],
                    'name' => $monster['name'],
                ];
            }),
        ]);
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
