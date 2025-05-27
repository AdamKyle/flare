<?php

namespace App\Game\Maps\Listeners;

use App\Flare\Models\Character;
use App\Game\Character\Builders\InformationBuilders\CharacterStatBuilder;
use App\Game\Maps\Events\MoveTimeOutEvent;
use App\Game\Maps\Events\ShowTimeOutEvent;
use App\Game\Maps\Jobs\MoveTimeOutJob;

class MoveTimeOutListener
{
    private CharacterStatBuilder $characterStatBuilder;

    public function __construct(CharacterStatBuilder $characterStatBuilder)
    {
        $this->characterStatBuilder = $characterStatBuilder;
    }

    /**
     * Handle the event.
     */
    public function handle(MoveTimeOutEvent $event): void
    {
        $character = $event->character;

        $this->characterStatBuilder = $this->characterStatBuilder->setCharacter($character);

        if ($event->traverse) {
            $time = $event->timeOut;

            MoveTimeOutJob::dispatch($character->id)->delay($time);
        } elseif ($event->timeOut !== 0) {
            $time = $this->disPatchMinuteBasedMovementTimeout($event, $character);
        } else {
            $this->dispatchWalkingTimeOut($event, $character);

            $time = 10;
        }

        event(new ShowTimeOutEvent($event->character->user, true, false, $time, $event->setSail));
    }

    /**
     * Dispatches the minute based movement timeout.
     *
     * - Applies skill bonus reductions to time.
     */
    protected function disPatchMinuteBasedMovementTimeout(MoveTimeOutEvent $event, Character $character): int
    {
        $time = (int) round($event->timeOut - ($event->timeOut * $this->characterStatBuilder->buildTimeOutModifier('move_time_out')));

        if ($time < 1) {
            $timeOut = now()->addMinute();
            $time = 1;
        } else {
            $timeOut = now()->addMinutes($time);
        }

        $character->update([
            'can_move' => false,
            'can_move_again_at' => $timeOut,
        ]);

        $character = $character->refresh();

        MoveTimeOutJob::dispatch($character->id)->delay($timeOut);

        return $time * 60;
    }

    /**
     * Dispatches the walking movement timer.
     */
    protected function dispatchWalkingTimeOut(MoveTimeOutEvent $event, Character $character): void
    {
        $timeOut = now()->addSeconds(10);

        $character->update([
            'can_move' => false,
            'can_move_again_at' => $timeOut,
        ]);

        $character = $character->refresh();

        MoveTimeOutJob::dispatch($character->id)->delay($timeOut);
    }
}
