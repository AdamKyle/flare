<?php

namespace Tests\Traits;

use App\Flare\Models\GameClass;

trait CreateClass {

    public function createClass(array $options = []) {
        return factory(GameClass::class)->create($options);
    }
}
