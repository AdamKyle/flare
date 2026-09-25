<?php

namespace App\Game\Messages\Services;

use App\Flare\Models\Announcement;
use App\Game\Events\Values\EventType;
use App\Game\Raids\Contracts\RaidIdentityQuery;
use Carbon\Carbon;

class AnnouncementPresenter
{
    private const ENDED_EVENT_NAME = 'Ended Event';

    /**
     * @param RaidIdentityQuery $raidIdentityQuery
     */
    public function __construct(
        private readonly RaidIdentityQuery $raidIdentityQuery,
    ) {}

    /**
     * Decorate the Announcement with its formatted expiration and event display
     * name, attaching minimal Raid identity for Raid events, so the API fetch
     * and broadcast paths expose the exact same shape.
     *
     * @param Announcement $announcement
     * @return Announcement
     */
    public function present(Announcement $announcement): Announcement
    {
        $announcement->expires_at_formatted = (new Carbon($announcement->expires_at))->format('l, j \of F \a\t h:ia \G\M\TP');

        // Deleting an Event nulls its Announcement's event_id rather than
        // removing the row, so an Announcement can outlive its Event.
        if (is_null($announcement->event)) {
            $announcement->event_name = self::ENDED_EVENT_NAME;

            return $announcement;
        }

        $announcement->event_name = $this->eventDisplayName($announcement);

        return $announcement;
    }

    /**
     * Resolve the Announcement's event display name, attaching minimal Raid
     * identity onto the event when the Announcement is for a Raid event.
     *
     * @param Announcement $announcement
     * @return string
     */
    private function eventDisplayName(Announcement $announcement): string
    {
        $eventType = new EventType($announcement->event->type);

        if (! $eventType->isRaidEvent() || is_null($announcement->event->raid_id)) {
            return $eventType->getNameForEvent();
        }

        $raidIdentity = $this->raidIdentityQuery->forId($announcement->event->raid_id);

        if (is_null($raidIdentity)) {
            return $eventType->getNameForEvent();
        }

        $announcement->event->raid_identity = [
            'id' => $raidIdentity->id,
            'raid_type' => $raidIdentity->raidType,
            'name' => $raidIdentity->name,
        ];

        return $raidIdentity->name;
    }
}
