<?php

namespace App\Game\Kingdoms\Controllers\Api;

use App\Flare\Models\Kingdom;
use App\Flare\Models\KingdomLog;
use App\Flare\Values\KingdomLogStatusValue;
use App\Game\Kingdoms\Handlers\KingdomHandler;
use App\Game\Kingdoms\Handlers\NotifyHandler;
use App\Game\Kingdoms\Requests\UseItemsRequest;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Http\Controllers\Controller;
use App\Flare\Models\Character;
use App\Game\Kingdoms\Requests\AttackRequest;
use App\Game\Kingdoms\Requests\SelectedKingdomsRequest;
use App\Game\Kingdoms\Service\KingdomsAttackService;

class KingdomAttackController extends Controller {

    /**
     * @var KingdomsAttackService $kingdomAttackService
     */
    private $kingdomAttackService;

    public function __construct(KingdomsAttackService $kingdomAttackService) {
        $this->middleware('is.character.dead');

        $this->kingdomAttackService = $kingdomAttackService;
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

        return response()->json([
            'kingdoms' => $kingdoms->toArray(),
            'items'    => array_values($usableItems),
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

    public function useItems(UseItemsRequest $request, Character $character, NotifyHandler $notifyHandler, KingdomHandler $kingdomHandler) {
        $damageToKingdom = 0.0;

        $slots = $character->inventory->slots()->whereIn('id', $request->slots_selected)->get();

        foreach ($slots as $slot) {
            $damageToKingdom += $slot->item->kingdom_damage;

            $slot->delete();
        }

        $kingdom    = Kingdom::with('buildings', 'units')->find($request->defender_id);
        $defender   = $kingdom->character;
        $oldKingdom = $kingdom->toArray();
        $buildings  = $kingdom->buildings;
        $units      = $kingdom->units;

        foreach ($buildings as $building) {
            $newDurability =  round($building->current_durability - ($building->current_durability * $damageToKingdom));

            if ($newDurability < 0) {
                $newDurability = 0;
            }

            $building->update([
                'current_durability' => $newDurability,
            ]);
        }

        foreach ($units as $unit) {
            $newAmount = round($unit->amount - ($unit->amount * $damageToKingdom));

            if ($newAmount < 0) {
                $newAmount = 0;
            }

            $unit->update([
                'amount' => $newAmount
            ]);
        }

        $kingdom = $kingdomHandler->setKingdom($kingdom)
                                  ->decreaseMorale()
                                  ->getKingdom();

        if (!is_null($defender)) {
            KingdomLog::create([
                'character_id'    => $defender->id,
                'status'          => KingdomLogStatusValue::BOMBS_DROPPED,
                'old_defender'    => $oldKingdom,
                'new_defender'    => $kingdom->toArray(),
                'to_kingdom_id'   => $kingdom->id,
                'published'       => true,
            ]);

            $message = 'Your kingdom ' . $kingdom->name . ' at (X/Y) ' . $kingdom->x_position .
                '/' . $kingdom->y_position . ' on the ' .
                $kingdom->gameMap->name . ' plane, has had an item dropped on it doing: ' . ($damageToKingdom * 100) . '% to Buildings and Units. Check your Attack logs for more info!';

            $notifyHandler->sendMessage($defender->user, 'kingdom-attacked', $message);
        }

        $message = $character->name . ' Has caused the earth to shake, the buildings to crumble and the units to slaughtered at: ' .
            $kingdom->name . ' (kingdom) doing: '.($damageToKingdom * 100).'% damage to units and buildings, on the ' . $kingdom->gameMap->name . ' plane. Even The Creator trembles in fear.';

        broadcast(new GlobalMessageEvent($message));

        return response()->json([
            'items' => array_values($character->inventory->slots->filter(function($slot) {
                return $slot->item->usable && $slot->item->damages_kingdoms;
            })->all()),
        ], 200);
    }
}
