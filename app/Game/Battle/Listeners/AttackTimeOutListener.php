<?php

namespace App\Game\Battle\Listeners;

use App\Game\Battle\Events\AttackTimeOutEvent;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Battle\Jobs\AttackTimeOutJob;
use App\Game\Character\Builders\InformationBuilders\CharacterStatBuilder;
use App\Game\Core\Events\ShowTimeOutEvent;
use Exception;

class AttackTimeOutListener
{
    private CharacterStatBuilder $characterStatBuilder;

    public function __construct(CharacterStatBuilder $characterStatBuilder)
    {
        $this->characterStatBuilder = $characterStatBuilder;
    }

    /**
     * Handle the event.
     *
     * @throws Exception
     */
    public function handle(AttackTimeOutEvent $event): void
    {
        $time = $event->character->is_dead ? 20 : 10;

        if ($time === 10) {
            $time = $time - ($time * $this->characterStatBuilder->setCharacter($event->character)->buildTimeOutModifier('fight_time_out'));
        }

        if ($time < 5) {
            $time = 5;
        }

        $event->character->update([
            'can_attack' => false,
            'can_attack_again_at' => now()->addSeconds($time),
        ]);

        event(new UpdateCharacterStatus($event->character));

        event(new ShowTimeOutEvent($event->character->user, $time));

        AttackTimeOutJob::dispatch($event->character)->delay(now()->addSeconds($time));
    }
}
