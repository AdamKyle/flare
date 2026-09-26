<?php

namespace App\Game\Skills\Values;

enum SkillBoonBonusAttribute: string
{
    case INCREASE_SKILL_BONUS_BY = 'increase_skill_bonus_by';
    case INCREASE_SKILL_TRAINING_BONUS_BY = 'increase_skill_training_bonus_by';
    case BASE_DAMAGE_MOD_BONUS = 'base_damage_mod_bonus';
    case BASE_HEALING_MOD_BONUS = 'base_healing_mod_bonus';
    case BASE_AC_MOD_BONUS = 'base_ac_mod_bonus';
}
