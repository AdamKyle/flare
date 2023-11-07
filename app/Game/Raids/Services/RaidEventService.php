<?php

namespace App\Game\Raids\Services;

use App\Flare\Models\Event;
use App\Flare\Models\Raid;
use App\Game\Events\Values\EventType;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Raids\Jobs\InitiateRaid;

class RaidEventService {


    /**
     * Create a raid event if one does not exist.
     *
     * @param Raid $raid
     * @return void
     */
    public function createRaid(Raid $raid): void {

        $existingRaid = Event::where('type', EventType::RAID_EVENT)->first();

        if (!is_null($existingRaid)) {
            return;
        }

        event(new GlobalMessageEvent($raid->name . ' is about to start soon! In one moment, the story will play out in chat,
        and players will be able to participate in this weeks raid event!'));

        $raidStory = preg_split('/(?<=[.?!])\s+(?=[a-z])/i', $raid->story);

        InitiateRaid::dispatch($raid->id, $raidStory)->delay(now()->addMinute());
    }
}
