<?php

namespace App\Flare\Listeners;

use App\Flare\Events\ServerMessageEvent;
use App\Flare\Models\Adventure;
use App\Flare\Models\Skill;
use App\Flare\Events\UpdateSkillEvent;
use App\Flare\Events\SkillLeveledUpServerMessageEvent;
use Facades\App\Flare\Calculators\SkillXPCalculator;

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
        if ($event->skill->max_level <= $event->skill->level) {
            
            return;
        }

        $event->skill->update([
            'xp' => $event->skill->xp + SkillXPCalculator::fetchSkillXP($event->skill, $event->adventure),
        ]);

        $skill = $event->skill->refresh();

        if ($skill->xp >= $skill->xp_max) {
            $level = $skill->level + 1;

            $skill->update([
                'level'              => $level,
                'xp_max'             => $skill->can_train ? rand(100, 150) : rand(100, 200),
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
