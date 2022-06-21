<?php

namespace App\Game\Kingdoms\Builders;

use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;

class KingdomBuilder {


    /**
     * Creates the kingdom
     *
     * @param Character $character
     * @param string $name
     * @return Kingdom
     */
    public function createKingdom(Character $character, string $name, string $color): Kingdom {
        $kingdom = [
            'name'                    => $name,
            'color'                   => $color,
            'character_id'            => $character->id,
            'game_map_id'             => $character->map->gameMap->id,
            'max_stone'               => 2000,
            'max_wood'                => 2000,
            'max_clay'                => 2000,
            'max_iron'                => 2000,
            'current_stone'           => 2000,
            'current_wood'            => 2000,
            'current_clay'            => 2000,
            'current_iron'            => 2000,
            'current_population'      => 100,
            'max_population'          => 100,
            'current_morale'          => .50,
            'max_morale'              => .50,
            'treasury'                => 0,
            'x_position'              => $character->map->character_position_x,
            'y_position'              => $character->map->character_position_y,
            'last_walked'             => now(),
        ];

        return Kingdom::create($kingdom);
    }
}
