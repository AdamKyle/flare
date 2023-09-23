<?php

namespace Tests\Traits;

use App\Flare\Models\GuideQuest;

trait CreateGuideQuest {

    public function createGuideQuest(array $options = []): GuideQuest {
        return GuideQuest::factory()->create($options);
    }
}
