<?php

namespace Tests\Traits;

use App\Flare\Models\Drop;

trait CreateDrops {

    public function createDrops(array $options = []) {
        return factory(Drop::class)->create($options);
    }
}
