<?php

namespace Tests\Traits;

use App\Flare\Models\Kingdom;

trait CreateKingdom {

    public function createKingdom(array $options = []): Kingdom {
        return Kingdom::factory()->create($options);
    }
}
