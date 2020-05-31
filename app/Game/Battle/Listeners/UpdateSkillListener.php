<?php

namespace App\Game\Battle\Listeners;

use App\Flare\Events\ServerMessageEvent;
use App\Flare\Models\Skill;
use App\Game\Battle\Events\UpdateSkillEvent;
use App\Game\Messages\Events\SkillLeveledUpServerMessageEvent;

class UpdateSkillListener
{

    public function __construct() {
    }

    /**
     * Handle the event.
     *
     * @param  \App\Game\Battle\UpdateSkillEvent  $event
     * @return void
     */
    public function handle(UpdateSkillEvent $event)
    {
        $equipmentBonus = $this->fetchSkilltrainingBonusFromEquipment($event->skill);
        $questItemBonus = $this->fetchSkilltrainingBonusFromQuestItems($event->skill);

        $event->skill->update([
            'xp' => $event->skill->xp + (10 * (1 + ($event->skill->xp_towards + $equipmentBonus + $questItemBonus))),
        ]);

        $skill = $event->skill->refresh();

        if ($skill->xp >= $skill->xp_max) {
            if ($skill->level <= $skill->max_level) {
                $level      = $skill->level + 1;
                $skillBonus = $skill->skill_bonus + $skill->skill_bonus_per_level;

                $skill->update([
                    'level'       => $level,
                    'xp_twoards'  => $skill->can_train ? rand(100, 150) : rand(50, 100),
                    'skill_bonus' => $skillBonus,
                    'xp'          => 0
                ]);

                event(new SkillLeveledUpServerMessageEvent($skill->character->user, $skill->refresh()));
            }
        }
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
}
