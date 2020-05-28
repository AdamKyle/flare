<?php

namespace App\Game\Battle\Listeners;

use App\Flare\Events\ServerMessageEvent;
use App\Flare\Models\Skill;
use App\Game\Battle\Events\UpdateSkillEvent;

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
            'xp' => ($event->skill->xp) + (10 * (1 + $event->skill->xp_towards)),
        ]);

        $skill = $event->skill->refresh();

        if ($skill->xp >= $skill->xp_max) {
            if ($skill->level <= $skill->max_level) {
                $level      = $skill->level + 1;
                $xpTwoards  = rand(100, 350);
                $skillBonus = $skill->skill_bonus + $skill->skill_bonus_per_level;

                $skill->update([
                    'level'       => $level,
                    'xp_twoards'  => rand(100, 350),
                    'skill_bonus' => $skillBonus,
                    'xp'          => 0
                ]);

                event(new ServerMessageEvent($event->skill->character->user, 'skill_level_up'));
            }
        }
    }

}
