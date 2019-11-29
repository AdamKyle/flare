<?php

namespace Tests\Traits;

use App\Flare\Models\Map;

trait CreateMap {

    public function createMap(array $options = []) {
        return factory(Map::class)->create($options);
    }
}
