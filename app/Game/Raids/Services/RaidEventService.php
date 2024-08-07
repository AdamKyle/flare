<?php

namespace App\Game\Raids\Services;

use App\Flare\Models\Event;
use App\Flare\Models\Raid;
use App\Flare\Models\ScheduledEvent;
use App\Game\Events\Values\EventType;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Raids\Jobs\InitiateRaid;

class RaidEventService
{
    /**
     * Create a raid event if one does not exist.
     */
    public function createRaid(Raid $raid): void
    {

        $existingRaid = Event::where('type', EventType::RAID_EVENT)->where('raid_id', $raid->id)->first();
        $scheduledEvent = ScheduledEvent::where('raid_id', $raid->id)->first();

        if (! is_null($existingRaid) || is_null($scheduledEvent)) {
            return;
        }

        event(new GlobalMessageEvent($raid->name.' is about to start soon! In one moment, the story will play out in chat,
        and players will be able to participate in this weeks raid event!'));

        $raidStory = preg_split('/(?<=[.?!])\s+(?=[a-z])/i', $raid->story);

        InitiateRaid::dispatch($scheduledEvent->id, $raidStory)->delay(now()->addMinute());
    }
}
