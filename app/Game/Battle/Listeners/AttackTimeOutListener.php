<?php

namespace App\Game\Battle\Listeners;

use App\Flare\Builders\Character\ClassDetails\ClassBonuses;
use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Skill;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Battle\Events\AttackTimeOutEvent;
use App\Game\Core\Events\ShowTimeOutEvent;
use App\Game\Battle\Jobs\AttackTimeOutJob;
use App\Game\Skills\Values\SkillTypeValue;
use Exception;

class AttackTimeOutListener {

    /**
     * @var ClassBonuses $classBonuses
     */
    private ClassBonuses $classBonuses;

    /**
     * @param ClassBonuses $classBonuses
     */
    public function __construct(ClassBonuses $classBonuses) {
        $this->classBonuses = $classBonuses;
    }

    /**
     * Handle the event.
     *
     * @param AttackTimeOutEvent $event
     * @return void
     * @throws Exception
     */
    public function handle(AttackTimeOutEvent $event): void {
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

        event(new UpdateCharacterStatus($event->character));

        event(new ShowTimeOutEvent($event->character->user, $time));

        AttackTimeOutJob::dispatch($event->character)->delay(now()->addSeconds($time));
    }

    /**
     * Fetch Timer reductions.
     *
     * @param Character $character
     * @return float|int
     * @throws Exception
     */
    protected function findTimeReductions(Character $character): float|int {

        $gameSkill = GameSkill::where('type', '=', SkillTypeValue::EFFECTS_BATTLE_TIMER)->first();
        $skill     = Skill::where('character_id', $character->id)->where('game_skill_id', $gameSkill->id)->first();

        if (is_null($skill)) {
            return 0;
        }

        $classBonus = $this->classBonuses->getThievesFightTimeout($character) + $this->classBonuses->getRangersFightTimeout($character);

        return $skill->fight_time_out_mod + $classBonus;
    }
}
