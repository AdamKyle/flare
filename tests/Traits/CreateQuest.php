<?php

namespace Tests\Traits;

use App\Flare\Models\Quest;

trait CreateQuest {

    public function createQuest(array $options = []): Quest {
        return Quest::factory()->create($options);
    }
}
