<?php

namespace App\Flare\Calculators;

use App\Flare\Models\Adventure;
use App\Flare\Models\Skill;

class SkillXPCalculator {

    public function fetchSkillXP(Skill $skill, Adventure $adventure = null) {
        $equipmentBonus = $this->fetchSkilltrainingBonusFromEquipment($skill);
        $questItemBonus = $this->fetchSkilltrainingBonusFromQuestItems($skill);
        $adventureBonus = $this->fetchAdventureBonus($adventure);

        return (10 * (1 + ($skill->xp_towards + $equipmentBonus + $questItemBonus + $adventureBonus)));
    }

    protected function fetchSkilltrainingBonusFromEquipment(Skill $skill): float {
        $totalSkillBonus = 0.0;

        foreach ($skill->character->inventory->slots as $slot) {
            if ($slot->equipped) {
                $totalSkillBonus += $slot->item->getSkillTrainingBonus($skill->name);
            }
        }

        return $totalSkillBonus;
    }

    protected function fetchSkilltrainingBonusFromQuestItems(Skill $skill): float {
        $totalSkillBonus = 0.0;

        foreach ($skill->character->inventory->questItemSlots as $slot) {
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