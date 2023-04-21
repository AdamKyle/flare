<?php

namespace Tests\Traits;

use App\Flare\Models\Quest;
use App\Flare\Models\QuestsCompleted;

trait CreateQuest {

    public function createQuest(array $options = []): Quest {
        return Quest::factory()->create($options);
    }

    public function createCompletedQuest(array $options = []): QuestsCompleted {
        return QuestsCompleted::factory()->create($options);
    }
}
