<?php

namespace Tests\Traits;

use App\Flare\Models\Skill;

trait CreateSkill {

    public function createSkill(array $options = []): Skill {
        return factory(Skill::class)->create($options);
    }
}
