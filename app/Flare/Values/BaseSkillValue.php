<?php

namespace App\Flare\Values;

use Illuminate\Support\Str;
use App\Flare\Models\Character;
use App\Flare\Models\Monster;

class BaseSkillValue {

    public function getBaseCharacterSkillValue(Character $character, string $name): array {

        return [
            'character_id'            => $character->id,
            'monster_id'              => null,
            'name'                    => $name,
            'currently_training'      => false,
            'level'                   => 1,
            'xp'                      => 0,
            'xp_max'                  => 100,
            'skill_bonus'             => $this->getCharacterSkillBonus($character, $name),
            'skill_bonus_per_level'   => 1,
        ];
    }

    public function getBaseMonsterSkillValue(Monster $monster, string $name): array {

        return [
            'character_id'            => null,
            'monster_id'              => $monster->id,
            'name'                    => $name,
            'currently_training'      => false,
            'level'                   => 1,
            'xp'                      => 0,
            'xp_max'                  => 100,
            'skill_bonus'             => 0,
            'skill_bonus_per_level'   => 1,
        ];
    }

    protected function getCharacterSkillBonus(Character $character, string $name): int {
      $raceSkillBonusValue  = $character->race->{Str::snake($name . '_mod')};
      $classSkillBonusValue = $character->class->{Str::snake($name . '_mod')};

      if (!is_null($raceSkillBonusValue) && !is_null($classSkillBonusValue)) {
          return $raceSkillBonusValue + $classSkillBonusValue;
      }

      return 0;
    }
}
