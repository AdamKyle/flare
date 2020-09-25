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
        $event->skill->update([
            'xp' => $event->skill->xp + SkillXPCalculator::fetchSkillXP($event->skill, $event->adventure),
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
}
