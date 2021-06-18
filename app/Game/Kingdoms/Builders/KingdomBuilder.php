<?php

namespace App\Game\Kingdoms\Builders;

use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;

class KingdomBuilder {

    /**
     * @var array $kingdom
     */
    private $kingdom;

    /**
     * Creates the base params for the kingdom based off the request.
     *
     * @param array $params
     * @return void
     */
    public function setRequestAttributes(array $params): void {
        $this->kingdom = $params;
    }

    /**
     * Creates the kingdom
     *
     * @param Character $character
     * @return Kingdom
     */
    public function createKingdom(Character $character): Kingdom {
        $kingdom = [
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
            'last_walked'             => now(),
        ];

        return Kingdom::create(array_merge($kingdom, $this->kingdom));
    }
}
