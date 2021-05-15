<?php

namespace App\Flare\Calculators;

use App\Flare\Models\Adventure;
use App\Flare\Models\Skill;

class SkillXPCalculator {

    /**
     * Fetches the total skill exp.
     *
     * Applies equipment, quest item, adventure bonuses and percentage of xp towards, to skill exp which starts at a
     * a base of 10.
     *
     * @param Skill $skill
     * @param Adventure $adventure | null
     */
    public function fetchSkillXP(Skill $skill, Adventure $adventure = null) {
        $equipmentBonus = $this->fetchSkillTrainingBonusFromEquipment($skill);
        $questItemBonus = $this->fetchSkillTrainingBonusFromQuestItems($skill);
        $adventureBonus = $this->fetchAdventureBonus($adventure);

        return (10 * (1 + ($skill->xp_towards + $equipmentBonus + $questItemBonus + $adventureBonus)));
    }

    protected function fetchSkillTrainingBonusFromEquipment(Skill $skill): float {
        $totalSkillBonus = 0.0;

        foreach ($skill->character->inventory->slots as $slot) {
            if ($slot->equipped) {
                $totalSkillBonus += $slot->item->getSkillTrainingBonus($skill->name);
            }
        }

        return $totalSkillBonus;
    }

    protected function fetchSkillTrainingBonusFromQuestItems(Skill $skill): float {
        $totalSkillBonus = 0.0;

        foreach ($skill->character->inventory->slots as $slot) {
            $totalSkillBonus += $slot->item->getSkillTrainingBonus($skill->name);
        }

        return $totalSkillBonus;
    }

    protected function fetchAdventureBonus(Adventure $adventure = null): float {
        if (!is_null($adventure)) {
            return $adventure->skill_exp_bonus;
        }

        return 0.0;
    }
}
