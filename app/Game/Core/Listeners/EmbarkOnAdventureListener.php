<?php

namespace App\Game\Core\Listeners;

use App\Game\Core\Events\EmbarkOnAdventureEvent;
use App\Game\Core\Jobs\AdventureJob;

class EmbarkOnAdventureListener
{

    public function __construct() {}

    public function handle(EmbarkOnAdventureEvent $event)
    {
        if ($event->levelsAtATime === 'all') {
            $timeTillFinished = now()->addMinutes($event->adventure->levels * $event->adventure->time_per_level);

            $event->character->update([
                'can_adventure_again_at' => $timeTillFinished,
            ]);

            return AdventureJob::dispatch($event->character->refresh(), $event->adventure, $event->levelsAtATime)->delay($timeTillFinished);
        }
    }
}
