<?php

namespace App\Game\Factions\FactionLoyalty\Services;


use App\Flare\Models\Character;
use App\Flare\Models\Npc;
use App\Game\Core\Traits\ResponseBuilder;

class FactionLoyaltyService {

    use ResponseBuilder;

    public function getLoyaltyInfoForPlane(Character $character): array {
        $gameMap = $character->map->gameMap;

        $npcNames = Npc::where('game_map_id', $gameMap->id)->get()->map(function($npc) {
            return [
                'id'   => $npc->id,
                'name' => $npc->real_name
            ];
        });

        return $this->successResult([
            'npcs' => $npcNames,
            'map_name' => $gameMap->name,
        ]);
    }
}
