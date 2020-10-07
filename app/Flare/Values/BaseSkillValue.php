<?php

namespace App\Flare\Values;

use Illuminate\Support\Str;
use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Monster;

class BaseSkillValue {

    public function getBaseCharacterSkillValue(Character $character, GameSkill $skill): array {
        
       return [
            'character_id'       => $character->id,
            'game_skill_id'      => $skill->id,
            'currently_training' => false,
            'level'              => 1,
            'xp'                 => 0,
            'xp_max'             => $skill->can_train ? rand(100, 150) : rand(100, 200),
            'base_damage_mod'    => 0,
            'base_healing_mod'   => 0,
            'base_ac_mod'        => 0,
            'fight_time_out_mod' => 0,
            'move_time_out_mod'  => 0,
            'skill_bonus'        => $this->getCharacterSkillBonus($character, $skill->name),
        ];
    }

    public function getBaseMonsterSkillValue(Monster $monster, GameSkill $skill): array {

        return [
            'monster_id'         => $monster->id,
            'game_skill_id'      => $skill->id,
            'currently_training' => false,
            'level'              => 0,
            'xp'                 => 0,
            'base_damage_mod'    => 0,
            'base_healing_mod'   => 0,
            'base_ac_mod'        => 0,
            'fight_time_out_mod' => 0,
            'move_time_out_mod'  => 0,
            'skill_bonus'        => 0.01,
        ];
    }

    protected function getCharacterSkillBonus(Character $character, string $name): int {
      $raceSkillBonusValue  = $character->race->{Str::snake($name . '_mod')};
      $classSkillBonusValue = $character->class->{Str::snake($name . '_mod')};

      if (!is_null($raceSkillBonusValue) && !is_null($classSkillBonusValue)) {
          return round($raceSkillBonusValue + $classSkillBonusValue);
      }

      return 0;
    }
}
