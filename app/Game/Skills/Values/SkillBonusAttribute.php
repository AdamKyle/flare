<?php

namespace App\Game\Skills\Values;

enum SkillBonusAttribute: string
{
    case SKILL_BONUS = 'skill_bonus';
    case SKILL_TRAINING_BONUS = 'skill_training_bonus';
    case BASE_DAMAGE_MOD = 'base_damage_mod';
    case BASE_HEALING_MOD = 'base_healing_mod';
    case BASE_AC_MOD = 'base_ac_mod';
    case FIGHT_TIME_OUT_MOD_BONUS = 'fight_time_out_mod_bonus';
    case MOVE_TIME_OUT_MOD_BONUS = 'move_time_out_mod_bonus';
}
