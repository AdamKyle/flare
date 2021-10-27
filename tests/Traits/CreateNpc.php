<?php

namespace Tests\Traits;

use App\Flare\Models\GameMap;
use Str;
use App\Flare\Models\Npc;
use App\Flare\Values\NpcCommandTypes;

trait CreateNpc {

    use CreateGameMap;

    public function createNpc(array $options = [], array $commandOptions = []): Npc {
        $gameMap = GameMap::first();

        $npc = Npc::factory()->create(array_merge([
            'game_map_id'  => !is_null($gameMap) ? $gameMap->id : $this->createGameMap()->id,
        ], $options));

        $npc->commands()->create(array_merge([
            'npc_id' => $npc->id,
            'command' => Str::random(10),
            'command_type' => NpcCommandTypes::TAKE_KINGDOM,
        ], $commandOptions));

        return $npc;
    }
}
