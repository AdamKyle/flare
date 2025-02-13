<?php

namespace Tests\Unit\Game\Kingdoms\Helpers;

use App\Flare\Models\GameMap;
use App\Flare\Models\Kingdom;
use Tests\Setup\Character\CharacterFactory;
use Tests\Traits\CreateGameBuilding;
use Tests\Traits\CreateKingdom;

trait CreateKingdomHelper {

    use CreateKingdom, CreateGameBuilding;

    protected function createKingdomForCharacter(?CharacterFactory $character): ?Kingdom
    {

        if (is_null($character)) {
            return null;
        }

        $gameMap = GameMap::first();

        if (is_null($gameMap)) {
            $this->fail('Was a game map created or a location given to the player?');
        }

        $kingdom = $this->createKingdom([
            'character_id' => $character->getCharacter()->id,
            'game_map_id' => $gameMap->id,
            'current_wood' => 500,
            'current_population' => 0,
            'last_walked' => now(),
        ]);

        $kingdom->buildings()->insert([
            [
                'game_building_id' => $this->createGameBuilding([
                    'is_farm' => true,
                    'decrease_morale_amount' => 0.20,
                    'increase_morale_amount' => 0.10,
                ])->id,
                'kingdom_id' => $kingdom->id,
                'level' => 1,
                'max_defence' => 100,
                'max_durability' => 100,
                'current_durability' => 100,
                'current_defence' => 100,
            ],
            [
                'game_building_id' => $this->createGameBuilding([
                    'is_resource_building' => true,
                    'increase_wood_amount' => 100,
                ])->id,
                'kingdom_id' => $kingdom->id,
                'level' => 1,
                'max_defence' => 100,
                'max_durability' => 100,
                'current_durability' => 100,
                'current_defence' => 100,
            ],
            [
                'game_building_id' => $this->createGameBuilding([
                    'is_resource_building' => true,
                    'increase_iron_amount' => 100,
                ])->id,
                'kingdom_id' => $kingdom->id,
                'level' => 1,
                'max_defence' => 100,
                'max_durability' => 100,
                'current_durability' => 100,
                'current_defence' => 100,
            ],
            [
                'game_building_id' => $this->createGameBuilding([
                    'is_resource_building' => true,
                    'increase_clay_amount' => 100,
                ])->id,
                'kingdom_id' => $kingdom->id,
                'level' => 1,
                'max_defence' => 100,
                'max_durability' => 100,
                'current_durability' => 100,
                'current_defence' => 100,
            ],
            [
                'game_building_id' => $this->createGameBuilding([
                    'is_resource_building' => true,
                    'increase_stone_amount' => 100,
                ])->id,
                'kingdom_id' => $kingdom->id,
                'level' => 1,
                'max_defence' => 100,
                'max_durability' => 100,
                'current_durability' => 100,
                'current_defence' => 100,
            ],
            [
                'game_building_id' => $this->createGameBuilding([
                    'name' => 'Keep',
                ])->id,
                'kingdom_id' => $kingdom->id,
                'level' => 1,
                'max_defence' => 100,
                'max_durability' => 100,
                'current_durability' => 100,
                'current_defence' => 100,
            ],
        ]);

        return $kingdom->refresh();
    }
}
