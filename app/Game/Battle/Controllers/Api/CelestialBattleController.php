<?php

namespace App\Game\Battle\Controllers\Api;

use App\Flare\Models\CelestialFight;
use App\Flare\Models\CharacterInCelestialFight;
use App\Flare\Models\Npc;
use App\Flare\Services\FightService;
use App\Flare\Values\NpcTypes;
use App\Game\Battle\Services\CelestialFightService;
use App\Game\Battle\Values\CelestialConjureType;
use App\Game\Messages\Builders\NpcServerMessageBuilder;
use App\Http\Controllers\Controller;
use App\Game\Battle\Request\ConjureRequest;
use App\Game\Battle\Services\ConjureService;
use App\Flare\Models\Character;
use App\Flare\Models\Monster;
use App\Game\Messages\Events\ServerMessageEvent;

class CelestialBattleController extends Controller {

    private $conjureService;

    private $npcServerMessage;

    private $celestialFightService;

    public function __construct(ConjureService $conjureService, NpcServerMessageBuilder $npcServerMessageBuilder, CelestialFightService $celestialFightService) {
        $this->conjureService        = $conjureService;
        $this->npcServerMessage      = $npcServerMessageBuilder;
        $this->celestialFightService = $celestialFightService;
    }

    public function celestialMonsters(Character $character) {
        $celestialBeings = Monster::select('name', 'gold_cost', 'gold_dust_cost', 'id')
                                  ->where('published', true)
                                  ->where('is_celestial_entity', true)
                                  ->where('game_map_id', $character->map->game_map_id)
                                  ->orderBy('max_level', 'asc')
                                  ->get();

        return response()->json([
            'celestial_monsters'  => $celestialBeings,
            'character_gold'      => $character->gold,
            'character_gold_dust' => $character->gold_dust,
        ], 200);
    }

    public function conjure(ConjureRequest $request, Character $character) {
        $npc     = Npc::where('type', NpcTypes::SUMMONER)->first();

        if (CelestialFight::where('character_id', $character->id)->get()->isNotEmpty()) {
            broadcast(new ServerMessageEvent($character->user, $this->npcServerMessage->build('already_conjured', $npc), true));

            return response()->json([], 200);
        }

        if ($request->type === 'public' && CelestialFight::where('type', CelestialConjureType::PUBLIC)->get()->isNotEmpty()) {
            broadcast(new ServerMessageEvent($character->user, $this->npcServerMessage->build('public_exists', $npc), true));

            return response()->json([], 200);
        }

        $monster = Monster::find($request->monster_id);

        if ($this->conjureService->canAfford($monster, $character)) {
            $this->conjureService->handleCost($monster, $character);

            $this->conjureService->conjure($monster, $character, $request->type);
        } else {
            broadcast(new ServerMessageEvent($character->user, $this->npcServerMessage->build('cant_afford_conjuring', $npc), true));

            return response()->json([], 200);
        }

        return response()->json([], 200);
    }

    public function fetchCelestialFight(Character $character, CelestialFight $celestialFight) {
        if ($character->is_dead) {
            broadcast(new ServerMessageEvent($character->user, 'You are dead and cannot participate.'));

            return response()->json([], 200);
        }

        if (!$character->can_adventure) {
            broadcast(new ServerMessageEvent($character->user, 'You are adventuring and cannot participate.'));

            return response()->json([], 200);
        }

        $characterInFight = $this->celestialFightService->joinFight($character, $celestialFight);

        return response()->json([
            'fight' => [
                'character' =>[
                    'max_health'     => $characterInFight->character_max_health,
                    'current_health' => $characterInFight->character_current_health,
                ],
                'monster' => [
                    'max_health'     => $celestialFight->max_health,
                    'current_health' => $celestialFight->current_health,
                ]
            ],
        ], 200);
    }

    public function attack(Character $character, CelestialFight $celestialFight) {
        if ($character->is_dead) {
            broadcast(new ServerMessageEvent($character->user, 'You are dead and cannot participate.'));

            return response()->json([], 200);
        }

        if (!$character->can_adventure) {
            broadcast(new ServerMessageEvent($character->user, 'You are adventuring and cannot participate.'));

            return response()->json([], 200);
        }

        $characterInCelestialFight = CharacterInCelestialFight::where('character_id', $character->id)->first();

        if (is_null($characterInCelestialFight)) {
            $characterInCelestialFight = $this->celestialFightService->joinFight($character, $celestialFight);
        }

        $response = $this->celestialFightService->fight($character, $celestialFight, $characterInCelestialFight);
        $status   = $response['status'];

        unset($response['status']);

        return response()->json($response, $status);
    }

    public function revive(Character $character) {
        $response = $this->celestialFightService->revive($character);

        $status   = $response['status'];

        unset($response['status']);

        return response()->json($response, $status);
    }
}
