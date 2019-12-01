<?php

namespace Tests\Traits;

use App\Flare\Models\Drop;

trait CreateDrops {

    public function createDrops(array $options = []): Drop {
        return factory(Drop::class)->create($options);
    }
}
