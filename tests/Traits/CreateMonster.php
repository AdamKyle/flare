<?php

namespace Tests\Traits;

use App\Flare\Models\GameSkill;
use App\Flare\Models\Monster;
use App\Flare\Values\BaseSkillValue;

trait CreateMonster {

    public function createMonster(array $options = []): Monster {
        $monster = Monster::factory()->create($options);

        foreach (GameSkill::where('specifically_assigned', false)->get() as $gameSkill) {
            if ($gameSkill->can_train) {
                $skills[] = resolve(BaseSkillValue::class)->getBaseMonsterSkillValue($monster, $gameSkill);
            }
        }

        $monster->skills()->insert($skills);

        return $monster->refresh();
    }
}
