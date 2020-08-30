<?php

namespace App\Game\Maps\Adventure\Listeners;

use App\Flare\Events\ServerMessageEvent;
use Cache;
use Illuminate\Support\Str;
use App\Game\Maps\Adventure\Jobs\AdventureJob;
use App\Game\Maps\Adventure\Events\EmbarkOnAdventureEvent;
use App\Game\Maps\Adventure\Events\UpdateAdventureLogsBroadcastEvent;

class EmbarkOnAdventureListener
{

    public function __construct() {}

    public function handle(EmbarkOnAdventureEvent $event)
    {
        $jobName = Str::random(80);

        if ($event->levelsAtATime === 'all') {
            $timeTillFinished = now()->addMinutes($event->adventure->levels * $event->adventure->time_per_level);
            $timeTillForget   = now()->addMinutes(($event->adventure->levels * $event->adventure->time_per_level) + 5);

            $event->character->update([
                'can_adventure_again_at' => $timeTillFinished,
            ]);

            Cache::put('character_'.$event->character->id.'_adventure_'.$event->adventure->id, $jobName, $timeTillForget);

            AdventureJob::dispatch($event->character->refresh(), $event->adventure, $event->levelsAtATime, $jobName)->delay($timeTillFinished);
        } else {
            $levels = $event->adventure->levels - (int) $event->levelsAtATime;

            if ($levels <= 0) {
                event(new ServerMessageEvent($event->character->user, 'adventure_error', 'Failed to initiate adventure. Invalid input'));

                $adventure = $event->character->adventureLogs->where('in_progress', true)->first();

                $adventure->update([
                    'in_progress' => false,
                ]);

                $character = $event->character->refresh();

                event(new UpdateAdventureLogsBroadcastEvent($character->adventureLogs, $event->character->user));

                $character->update([
                    'is_dead'                => false,
                    'can_move'               => true,
                    'can_attack'             => true,
                    'can_craft'              => true,
                    'can_adventure'          => true,
                    'can_adventure_again_at' => null,
                ]);
            } else {
                $timeTillFinished = now()->addMinutes($levels * $event->adventure->time_per_level);
                $timeTillForget   = now()->addMinutes(($levels * $event->adventure->time_per_level) + 5);

                $event->character->update([
                    'can_adventure_again_at' => $timeTillFinished,
                ]);
    
                Cache::put('character_'.$event->character->id.'_adventure_'.$event->adventure->id, $jobName, $timeTillForget);
    
                AdventureJob::dispatch($event->character->refresh(), $event->adventure, $levels, $jobName)->delay($timeTillFinished);
            }
        }
    }
}
