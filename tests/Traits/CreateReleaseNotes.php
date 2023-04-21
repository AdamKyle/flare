<?php

namespace Tests\Traits;

use App\Flare\Models\GameRace;
use App\Flare\Models\ReleaseNote;

trait CreateReleaseNotes {

    public function createReleaseNotes(array $options = []): ReleaseNote {
        return ReleaseNote::factory()->create($options);
    }
}
