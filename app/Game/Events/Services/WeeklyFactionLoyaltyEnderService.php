<?php

namespace App\Game\Events\Services;

use App\Flare\Models\Event as ActiveEvent;
use App\Flare\Models\ScheduledEvent;
use App\Game\Events\Services\Concerns\EventEnder;
use App\Game\Events\Values\EventType;
use App\Game\Messages\Events\GlobalMessageEvent;

class WeeklyFactionLoyaltyEnderService implements EventEnder {

    /**
     * @param AnnouncementCleanupService $announcementCleanup
     */
    public function __construct(private readonly AnnouncementCleanupService $announcementCleanup)
    {}

    /**
     * @param  EventType  $type
     * @return bool
     */
    public function supports(EventType $type): bool
    {
        return $type->isWeeklyFactionLoyaltyEvent();
    }

    /**
     * @param  EventType  $type
     * @param  ScheduledEvent  $scheduled
     * @param  ActiveEvent  $current
     * @return void
     */
    public function end(EventType $type, ScheduledEvent $scheduled, ActiveEvent $current): void
    {
        event(new GlobalMessageEvent('Weekly Faction Loyalty Event has come to an end. Next time Npc Tasks refresh from level up, they will be back to normal.'));

        $this->announcementCleanup->deleteByEventId($current->id);

        $current->delete();
    }
}
