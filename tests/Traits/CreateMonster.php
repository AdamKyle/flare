<?php

namespace Tests\Traits;

use App\Flare\Models\Monster;
use App\Flare\Values\BaseSkillValue;

trait CreateMonster {

    public function createMonster(array $options = []): Monster {
        $monster = Monster::factory()->create($options);

        foreach(config('game.skills') as $options) {
            $skills[] = resolve(BaseSkillValue::class)->getBaseMonsterSkillValue($monster, $options);
        }

        $monster->skills()->insert($skills);

        return $monster->refresh();
    }
}
