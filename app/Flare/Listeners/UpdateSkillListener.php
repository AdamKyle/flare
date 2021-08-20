<?php

namespace App\Flare\Listeners;

use Facades\App\Flare\Calculators\SkillXPCalculator;
use App\Flare\Events\UpdateSkillEvent;
use App\Flare\Events\SkillLeveledUpServerMessageEvent;

class UpdateSkillListener
{

    /**
     * Handle the event.
     *
     * @param  UpdateSkillEvent $event
     * @return void
     */
    public function handle(UpdateSkillEvent $event)
    {
        if ($event->skill->max_level <= $event->skill->level) {
            return;
        }

        $gameMap = $event->skill->character->map->gameMap;

        $skillXP = SkillXPCalculator::fetchSkillXP($event->skill, $event->adventure, $event->monster);

        if (!is_null($gameMap->skill_training_bonus)) {
            $skillXP = $skillXP + $skillXP * $gameMap->skill_training_bonus;
        }

        $event->skill->update([
            'xp' => $event->skill->xp + $skillXP,
        ]);

        $skill = $event->skill->refresh();

        if ($skill->xp >= $skill->xp_max) {
            $level = $skill->level + 1;

            $skill->update([
                'level'              => $level,
                'xp_max'             => $skill->can_train ? rand(150, 350) : rand(100, 250),
                'base_damage_mod'    => $skill->base_damage_mod + $skill->baseSkill->base_damage_mod_bonus_per_level,
                'base_healing_mod'   => $skill->base_healing_mod + $skill->baseSkill->base_healing_mod_bonus_per_level,
                'base_ac_mod'        => $skill->base_ac_mod + $skill->baseSkill->base_ac_mod_bonus_per_level,
                'fight_time_out_mod' => $skill->fight_time_out_mod + $skill->baseSkill->fight_time_out_mod_bonus_per_level,
                'move_time_out_mod'  => $skill->mov_time_out_mod + $skill->baseSkill->mov_time_out_mod_bonus_per_level,
                'skill_bonus'        => $skill->skill_bonus + $skill->baseSkill->skill_bonus_per_level,
                'xp'                 => 0,
            ]);

            event(new SkillLeveledUpServerMessageEvent($skill->character->user, $skill->refresh()));
        }
    }
}
