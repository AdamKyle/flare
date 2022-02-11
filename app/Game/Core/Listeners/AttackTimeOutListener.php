<?php

namespace App\Game\Core\Listeners;

use App\Flare\Builders\Character\ClassDetails\ClassBonuses;
use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Skill;
use App\Game\Core\Events\AttackTimeOutEvent;
use App\Game\Core\Events\ShowTimeOutEvent;
use App\Game\Core\Jobs\AttackTimeOutJob;
use App\Game\Skills\Values\SkillTypeValue;

class AttackTimeOutListener
{

    private $classBonuses;

    public function __construct(ClassBonuses $classBonuses) {
        $this->classBonuses = $classBonuses;
    }

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

        if ($time < 5) {
            $time = 5;
        }

        $event->character->update([
            'can_attack'          => false,
            'can_attack_again_at' => now()->addSeconds($time),
        ]);

        broadcast(new ShowTimeOutEvent($event->character->user, true, false, $time));

        AttackTimeOutJob::dispatch($event->character)->delay(now()->addSeconds($time));
    }

    protected function findTimeReductions(Character $character) {

        $gameSkill = GameSkill::where('type', '=', SkillTypeValue::EFFECTS_BATTLE_TIMER)->first();
        $skill     = Skill::where('character_id', $character)->where('game_skill_id', $gameSkill->id)->first();

        if (is_null($skill)) {
            return 0;
        }

        $classBonus = $this->classBonuses->getThievesFightTimeout($character) + $this->classBonuses->getRangersFightTimeout($character);

        return $skill->fight_time_out_mod + $classBonus;
    }
}
