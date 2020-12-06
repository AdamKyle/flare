<?php

namespace Tests\Traits;

use App\Flare\Models\GameSkill;
use App\Flare\Models\Monster;
use App\Flare\Values\BaseSkillValue;
use Illuminate\Database\Eloquent\Collection;

trait CreateMonster {

    public function createMonster(array $options = []): Monster {
        $monster     = Monster::factory()->create($options);
        $gameSkills  = $this->fetchSkills();
        $skills      = [];

        foreach ($gameSkills as $gameSkill) {
            if ($gameSkill->can_train) {
                $skills[] = resolve(BaseSkillValue::class)->getBaseMonsterSkillValue($monster, $gameSkill);
            }
        }

        $monster->skills()->insert($skills);

        return $monster->refresh();
    }

    protected function fetchSkills(): Collection {
        $skills = GameSkill::where('specifically_assigned', false)->get();

        if ($skills->isEmpty()) {
            $this->createGameSkill(['name' => 'Accuracy']);
            $this->createGameSkill(['name' => 'Dodge']);
            $this->createGameSkill(['name' => 'Looting']);

            return GameSkill::where('specifically_assigned', false)->get();
        }

        return $skills;
    }
}
