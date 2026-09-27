<?php

namespace App\Admin\PassiveSkills\Transformers;

use App\Flare\Models\PassiveSkill;

class PassiveSkillFormTransformer
{
    /**
     * Transform a Passive Skill into its Admin save-response / form-value representation.
     *
     * @param PassiveSkill $passiveSkill
     * @return array
     */
    public function transform(PassiveSkill $passiveSkill): array
    {
        return [
            'id' => $passiveSkill->id,
            'name' => $passiveSkill->name,
            'description' => $passiveSkill->description,
            'max_level' => $passiveSkill->max_level,
            'effect_type' => $passiveSkill->effect_type,
            'bonus_per_level' => $passiveSkill->bonus_per_level,
            'resource_bonus_per_level' => $passiveSkill->resource_bonus_per_level,
            'capital_city_building_request_travel_time_reduction' => $passiveSkill->capital_city_building_request_travel_time_reduction,
            'capital_city_unit_request_travel_time_reduction' => $passiveSkill->capital_city_unit_request_travel_time_reduction,
            'resource_request_time_reduction' => $passiveSkill->resource_request_time_reduction,
            'parent_skill_id' => $passiveSkill->parent_skill_id,
            'unlocks_at_level' => $passiveSkill->unlocks_at_level,
            'hours_per_level' => $passiveSkill->hours_per_level,
            'is_locked' => $passiveSkill->is_locked,
            'is_parent' => $passiveSkill->is_parent,
        ];
    }
}
