<?php

namespace App\Game\Events\Services;

use App\Flare\Events\UpdateScheduledEvents;
use App\Flare\Models\ScheduledEvent;
use App\Game\Events\Services\EventSchedulerService;

class ScheduleEventFinalizerService
{
    public function __construct(private readonly EventSchedulerService $eventSchedulerService) {}

    public function markNotRunningAndBroadcast(ScheduledEvent $scheduled): void
    {
        $scheduled->update(['currently_running' => false]);

        event(new UpdateScheduledEvents($this->eventSchedulerService->fetchEvents()));
    }
}
