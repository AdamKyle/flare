<?php

namespace App\Game\Kingdoms\Controllers\Api;

use App\Flare\Models\Kingdom;
use App\Game\Kingdoms\Requests\UseItemsRequest;
use App\Game\Kingdoms\Service\AttackWithItemsService;
use App\Game\Kingdoms\Service\UseItemsService;
use App\Http\Controllers\Controller;
use App\Flare\Models\Character;
use App\Game\Kingdoms\Requests\AttackRequest;
use App\Game\Kingdoms\Requests\SelectedKingdomsRequest;
use App\Game\Kingdoms\Service\KingdomAttackService;

class KingdomAttackController extends Controller {

    /**
     * @var KingdomAttackService $kingdomAttackService
     */
    private KingdomAttackService $kingdomAttackService;

    /**
     * @var AttackWithItemsService $attackWithItemsService
     */
    private AttackWithItemsService $attackWithItemsService;

    /**
     * @param KingdomAttackService $kingdomAttackService
     * @param AttackWithItemsService $attackWithItemsService
     */
    public function __construct(KingdomAttackService $kingdomAttackService, AttackWithItemsService $attackWithItemsService) {
        $this->middleware('is.character.dead');

        $this->kingdomAttackService   = $kingdomAttackService;
        $this->attackWithItemsService = $attackWithItemsService;

    }

    public function fetchKingdomsWithUnits(Character $character) {
        $kingdoms = $character->kingdoms()
                              ->where('game_map_id', $character->map->game_map_id)
                              ->join('kingdom_units', function($join) {
                                  $join->on('kingdoms.id', 'kingdom_units.kingdom_id')
                                       ->where('kingdom_units.amount', '>', 0);
                              })->select('kingdoms.id', 'kingdoms.name', 'kingdoms.x_position', 'kingdoms.y_position')
                                ->groupBy('kingdoms.id')
                                ->get();

        $usableItems = $character->inventory->slots->filter(function($slot) {
           return $slot->item->usable && $slot->item->damages_kingdoms;
        })->all();

        $unitTimeReductionSkill = $character->skills->filter(function($skill) {
            return $skill->type()->effectsKingdom();
        })->first();

        return response()->json([
            'kingdoms'                     => $kingdoms->toArray(),
            'items'                        => array_values($usableItems),
            'unit_movement_time_reduction' => $unitTimeReductionSkill->unit_movement_time_reduction,
        ], 200);
    }

    public function selectKingdoms(SelectedKingdomsRequest $request, Character $character) {
        $response = $this->kingdomAttackService->fetchSelectedKingdomData($character, $request->selected_kingdoms);

        $status = $response['status'];

        unset($response['status']);

        return response()->json($response, $status);
    }

    public function attack(AttackRequest $request, Character $character) {
        $response = $this->kingdomAttackService->attackKingdom($character, $request->defender_id, $request->units_to_send);

        $status = $response['status'];

        unset($response['status']);

        return response()->json($response, $status);
    }

    public function useItems(UseItemsRequest $request, Character $character) {

        $this->attackWithItemsService->useItemsOnKingdom($character, Kingdom::find($request->defender_id), $request->slots_selected);

        return response()->json([
            'items' => array_values($character->inventory->slots->filter(function($slot) {
                return $slot->item->usable && $slot->item->damages_kingdoms;
            })->all()),
        ], 200);
    }
}
