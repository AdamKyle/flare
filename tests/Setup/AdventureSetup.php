<?php

namespace Tests\Setup;

use App\Flare\Models\Adventure;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Monster;
use App\Flare\Values\BaseSkillValue;
use Tests\Traits\CreateAdventure;

class AdventureSetup {

    use CreateAdventure;

    private $monster = null;

    public function setMonster(Monster $monster, int $bonusIncrease = 0): AdventureSetup {
        $this->monster = $monster;

        return $this;
    }

    public function createAdventure(): Adventure {
        return $this->createNewAdventure($this->monster);
    }
}