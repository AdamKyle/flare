<?php

namespace App\Game\Events\Services;

use App\Flare\Models\Event as ActiveEvent;
use App\Flare\Models\ScheduledEvent;
use App\Game\Events\Services\Concerns\EventEnder;
use App\Game\Events\Values\EventType;
use App\Game\Messages\Events\GlobalMessageEvent;

class WeeklyCelestialEventEnderService implements EventEnder
{
    public function __construct(private readonly AnnouncementCleanupService $announcementCleanup) {}

    public function supports(EventType $type): bool
    {
        return $type->isWeeklyCelestials();
    }

    public function end(EventType $type, ScheduledEvent $scheduled, ActiveEvent $current): void
    {
        event(new GlobalMessageEvent(
            'The Creator has managed to close the gates and lock the Celestials away behind the doors of Kalitorm! Come back next week for another chance at the hunt!'
        ));

        $this->announcementCleanup->deleteByEventId($current->id);

        $current->delete();
    }
}
