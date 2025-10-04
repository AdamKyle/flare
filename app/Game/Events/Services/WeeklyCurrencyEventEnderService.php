<?php

namespace App\Game\Events\Services;

use App\Flare\Models\Event as ActiveEvent;
use App\Flare\Models\ScheduledEvent;
use App\Game\Events\Services\Concerns\EventEnder;
use App\Game\Events\Values\EventType;
use App\Game\Messages\Events\GlobalMessageEvent;

class WeeklyCurrencyEventEnderService implements EventEnder
{
    public function __construct(private readonly AnnouncementCleanupService $announcementCleanup) {}

    public function supports(EventType $type): bool
    {
        return $type->isWeeklyCurrencyDrops();
    }

    public function end(EventType $type, ScheduledEvent $scheduled, ActiveEvent $current): void
    {
        event(new GlobalMessageEvent('Weekly currency drops have come to an end! Come back next sunday for another chance!'));

        $this->announcementCleanup->deleteByEventId($current->id);

        $current->delete();
    }
}
