<?php

namespace Tests\Traits;

use App\Flare\Models\Monster;

trait CreateMonster {

    public function createMonster(array $options = []) {
        return factory(Monster::class)->create($options);
    }
}
