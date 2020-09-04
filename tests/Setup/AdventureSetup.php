<?php

namespace Tests\Setup;

use App\Flare\Models\Adventure;
use App\Flare\Models\Monster;
use App\Flare\Values\BaseSkillValue;
use Tests\Traits\CreateAdventure;

class AdventureSetup {

    use CreateAdventure;

    private $monster = null;

    public function setMonster(Monster $monster): AdventureSetup {
        $this->monster = $monster;

        foreach(config('game.skills') as $options) {
            $skills[] = resolve(BaseSkillValue::class)->getBaseMonsterSkillValue($this->monster, $options);
        }

        $monster->skills()->insert($skills);

        $this->monster->refresh();

        return $this;
    }

    public function createAdventure(): Adventure {
        return $this->createNewAdventure($this->monster);
    }
}