<?php

namespace Tests\Traits;

use App\Flare\Models\Location;

trait CreateLocation {

    public function createLocation(array $options = []): Location{
        return factory(Location::class)->create($options);
    }
}
