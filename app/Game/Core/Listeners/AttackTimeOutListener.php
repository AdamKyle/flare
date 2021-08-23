<?php

namespace App\Game\Core\Listeners;

use App\Flare\Models\Character;
use App\Flare\Traits\ClassBasedBonuses;
use App\Game\Core\Events\AttackTimeOutEvent;
use App\Game\Core\Events\ShowTimeOutEvent;
use App\Game\Core\Jobs\AttackTimeOutJob;

class AttackTimeOutListener
{

    use ClassBasedBonuses;

    /**
     * Handle the event.
     *
     * @param  \App\Game\Battle\UpdateCharacterEvent  $event
     * @return void
     */
    public function handle(AttackTimeOutEvent $event)
    {
        $time = $event->character->is_dead ? 20 : 10;

        if ($time === 10) {
            $time = $time - ($time * $this->findTimeReductions($event->character));
        }

        if ($time <= 0) {
            $time = 1;
        }

        $event->character->update([
            'can_attack'          => false,
            'can_attack_again_at' => now()->addSeconds($time),
        ]);

        broadcast(new ShowTimeOutEvent($event->character->user, true, false, $time));

        AttackTimeOutJob::dispatch($event->character)->delay(now()->addSeconds($time));
    }

    protected function findTimeReductions(Character $character) {
        $skill = $character->skills->filter(function($skill) {
            return ($skill->fight_time_out_mod > 0.0) && is_null($skill->baseSkill->game_class_id);
        })->first();

        if (is_null($skill)) {
            return 0;
        }

        $classBonus = $this->getThievesFightTimeout($character) + $this->getRangersFightTimeout($character);

        return $skill->fight_time_out_mod + $classBonus;
    }
}
