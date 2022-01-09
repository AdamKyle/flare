<?php

namespace Tests\Traits;

use App\Flare\Models\PassiveSkill;

trait CreatePassiveSkill {

    /**
     * @param array $options
     * @return PassiveSkill
     */
    public function createPassiveSkill(array $options = []): PassiveSkill {
        return PassiveSkill::factory()->create($options);
    }
}
