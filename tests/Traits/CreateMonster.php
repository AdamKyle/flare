<?php

namespace Tests\Traits;

use App\Flare\Models\GameMap;
use App\Flare\Models\Monster;

trait CreateMonster {

    use CreateGameMap;

    public function createMonster(array $options = []): Monster {
        if (empty($options) || !isset($options['game_map_id'])) {

            $maps = GameMap::all();

            if ($maps->isEmpty()) {
                $map = $this->createGameMap();
            } else {
                $map = $maps->first();
            }

            $options['game_map_id'] = $map->id;
        }

        $monster     = Monster::factory()->create($options);

        return $monster->refresh();
    }

    public function createMultipleMonsters(array $options = [], int $amount = 1) {
        for ($i = 1; $i <= $amount; $i++) {
            $this->createMonster($options);
        }
    }
}
