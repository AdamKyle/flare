<?php

namespace Tests\Traits;

use App\Flare\Models\Npc;
use App\Flare\Values\NpcCommandTypes;

trait CreateNpc {

    public function createNpc(array $options = [], array $commandOptions = []): Npc {
        $npc = Npc::factory()->create($options);

        $npc->commands()->create(array_merge([
            'npc_id' => $npc->id,
            'command' => 'Test',
            'command_type' => NpcCommandTypes::TAKE_KINGDOM,
        ], $commandOptions));

        return $npc;
    }
}
