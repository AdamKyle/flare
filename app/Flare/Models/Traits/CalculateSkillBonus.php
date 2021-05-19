<?php

namespace App\Flare\Models\Traits;

use App\Flare\Models\Item;

trait CalculateSkillBonus {

    /**
     * Calculates the skill bonus when using the skill.
     *
     * Takes into account the items and their associated prefixes.
     *
     * @param Item $item
     * @param string $skillName
     * @return float|mixed
     */
    public function calculateBonus(Item $item, string $skillName) {
        $baseSkillTraining = 0.0;

        if (!is_null($item->itemPrefix)) {
            if ($item->itemPrefix->skill_name === $skillName) {
                $baseSkillTraining += !is_null($item->itemPrefix->skill_bonus) ? $item->itemPrefix->skill_bonus : 0;
            }
        }

        if (!is_null($item->itemSuffix)) {
            if ($item->itemSuffix->skill_name === $skillName) {
                $baseSkillTraining += !is_null($item->itemSuffix->skill_bonus) ? $item->itemSuffix->skill_bonus : 0;
            }
        }

        if (!is_null($item->skill_name)) {
            if ($item->skill_name === $skillName) {
                $baseSkillTraining += $item->skill_bonus;
            }
        }

        return $baseSkillTraining;
    }

    /**
     * The percentage of XP bonus given when the skill is being awarded XP.
     *
     * @param Item $item
     * @param string $skillName
     * @return float
     */
    public function calculateTrainingBonus(Item $item, string $skillName) {
        $baseSkillTraining = 0.0;

        if (!is_null($item->itemPrefix)) {
            if ($item->itemPrefix->skill_name === $skillName) {
                $baseSkillTraining += !is_null($item->itemPrefix->skill_training_bonus) ? $item->itemPrefix->skill_training_bonus : 0;
            }
        }

        if (!is_null($item->itemSuffix)) {
            if ($item->itemSuffix->skill_name === $skillName) {

                $baseSkillTraining += !is_null($item->itemSuffix->skill_training_bonus) ? $item->itemSuffix->skill_training_bonus : 0;
            }

        }

        if (!is_null($item->skill_name)) {
            if ($item->skill_name === $skillName) {
                $baseSkillTraining += $item->skill_training_bonus;
            }
        }

        return $baseSkillTraining;
    }
}
