<?php

namespace App\Flare\Values;

use Illuminate\Support\Str;
use App\Flare\Models\Character;
use App\Flare\Models\Monster;

class BaseSkillValue {

    public function getBaseCharacterSkillValue(Character $character, array $options): array {

       return [
            'character_id'              => $character->id,
            'monster_id'                => null,
            'description'               => $options['description'],
            'name'                      => $options['name'],
            'currently_training'        => false,
            'level'                     => 0,
            'max_level'                 => 100,
            'xp'                        => 0,
            'xp_max'                    => rand(100, 350),
            'base_damage_mod'           => $options['base_damage_mod'],
            'base_healing_mod'          => $options['base_healing_mod'],
            'base_ac_mod'               => $options['base_ac_mod'],
            'fight_time_out_mod'        => $options['fight_time_out_mod'],
            'move_time_out_mod'         => $options['move_time_out_mod'],
            'skill_bonus'               => ($this->getCharacterSkillBonus($character, $options['name']) / 100) + 0.01,
            'skill_bonus_per_level'     => 0.01,
        ];
    }

    public function getBaseMonsterSkillValue(Monster $monster, array $options): array {

        return [
            'character_id'              => null,
            'monster_id'                => $monster->id,
            'description'               => $options['description'],
            'name'                      => $options['name'],
            'currently_training'        => false,
            'level'                     => 0,
            'max_level'                 => 100,
            'xp'                        => 0,
            'xp_max'                    => rand(100, 350),
            'base_damage_mod'           => $options['base_damage_mod'],
            'base_healing_mod'          => $options['base_healing_mod'],
            'base_ac_mod'               => $options['base_ac_mod'],
            'fight_time_out_mod'        => $options['fight_time_out_mod'],
            'move_time_out_mod'         => $options['move_time_out_mod'],
            'skill_bonus'               => 0.01,
            'skill_bonus_per_level'     => 0.01,
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
