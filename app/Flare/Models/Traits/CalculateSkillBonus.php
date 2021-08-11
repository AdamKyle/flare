<?php

namespace App\Flare\Models\Traits;

use App\Flare\Models\GameSkill;
use App\Flare\Models\Item;
use App\Flare\Models\ItemAffix;

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
    public function calculateBonus(Item $item, GameSkill $skill, string $skillAttribute = 'skill_bonus') {
        $baseSkillTraining = 0.0;

        if (!is_null($item->itemPrefix)) {
            if ($this->matchesSkillOnItem($item->itemPrefix, $skill)) {
                $baseSkillTraining += !is_null($item->itemPrefix->{$skillAttribute}) ? $item->itemPrefix->{$skillAttribute} : 0;
            }
        }

        if (!is_null($item->itemSuffix)) {
            if ($this->matchesSkillOnItem($item->itemSuffix, $skill)) {
                $baseSkillTraining += !is_null($item->itemSuffix->{$skillAttribute}) ? $item->itemSuffix->{$skillAttribute} : 0;
            }
        }

        if (!is_null($item->skill_name)) {
            if ($item->skill_name === $skill->name) {
                $baseSkillTraining += $item->{$skillAttribute};
            }
        }

        if (!is_null($item->{$skillAttribute})) {
            $baseSkillTraining += $item->{$skillAttribute};
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
    public function calculateTrainingBonus(Item $item, GameSkill $gameSkill) {
        $baseSkillTraining = 0.0;

        if (!is_null($item->itemPrefix)) {
            if ($this->matchesSkillOnItem($item->itemPrefix, $gameSkill)) {
                $baseSkillTraining += !is_null($item->itemPrefix->skill_training_bonus) ? $item->itemPrefix->skill_training_bonus : 0;
            }
        }

        if (!is_null($item->itemSuffix)) {
            if ($this->matchesSkillOnItem($item->itemSuffix, $gameSkill)) {

                $baseSkillTraining += !is_null($item->itemSuffix->skill_training_bonus) ? $item->itemSuffix->skill_training_bonus : 0;
            }

        }

        if (!is_null($item->skill_name)) {
            if ($item->skill_name === $gameSkill->name) {
                $baseSkillTraining += $item->skill_training_bonus;
            }
        }

        return $baseSkillTraining;
    }

    protected function matchesSkillOnItem(ItemAffix $itemAffix, GameSkill $skill): bool {
        return $itemAffix->skill_name === $skill->name || $itemAffix->affects_skill_type === $skill->type;
    }
}
