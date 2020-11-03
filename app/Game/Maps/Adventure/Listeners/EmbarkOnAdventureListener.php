<?php

namespace App\Game\Maps\Adventure\Listeners;

use App\Flare\Events\ServerMessageEvent;
use Cache;
use Illuminate\Support\Str;
use App\Game\Maps\Adventure\Jobs\AdventureJob;
use App\Game\Maps\Adventure\Events\EmbarkOnAdventureEvent;
use App\Game\Maps\Adventure\Events\UpdateAdventureLogsBroadcastEvent;
use Illuminate\Support\Facades\Bus;

class EmbarkOnAdventureListener
{

    public function __construct() {}

    public function handle(EmbarkOnAdventureEvent $event)
    {
        $jobName = Str::random(80);

        $timeTillFinished = now()->addMinutes($event->adventure->levels * $event->adventure->time_per_level);
        $timeTillForget   = now()->addMinutes(($event->adventure->levels * $event->adventure->time_per_level) + 5);

        $event->character->update([
            'can_adventure_again_at' => $timeTillFinished,
        ]);

        Cache::put('character_'.$event->character->id.'_adventure_'.$event->adventure->id, $jobName, $timeTillForget);
        
        $event->character->refresh();

        event(new UpdateAdventureLogsBroadcastEvent($event->character->adventureLogs, $event->character->user));
        
        $this->createJobs($event, $jobName);
    }

    protected function createJobs(EmbarkOnAdventureEvent $event, string $jobName): void {

        $character = $event->character->refresh();

        for($i = 1; $i <= $event->adventure->levels; $i++) {
            $delay            = $i === 1 ? $event->adventure->time_per_level : $i * $event->adventure->time_per_level;
            $timeTillFinished = now()->addMinutes($delay);

            AdventureJob::dispatch($character, $event->adventure, $jobName, $i)->delay($timeTillFinished);
        }
    }
}
