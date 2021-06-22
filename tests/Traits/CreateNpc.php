<?php

namespace Tests\Traits;

use App\Flare\Models\Npc;

trait CreateNpc {

    public function createNpc(array $options = []): Npc {
        return Npc::factory()->create($options);
    }
}
