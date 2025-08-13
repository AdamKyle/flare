<?php

namespace App\Game\Events\Services;

use App\Flare\Events\UpdateScheduledEvents;
use App\Flare\Models\ScheduledEvent;
use App\Flare\Services\EventSchedulerService;

class ScheduleEventFinalizerService
{

    /**
     * @param  EventSchedulerService  $eventSchedulerService
     */
    public function __construct(private readonly EventSchedulerService $eventSchedulerService)
    {}

    /**
     * @param  ScheduledEvent  $scheduled
     * @return void
     */
    public function markNotRunningAndBroadcast(ScheduledEvent $scheduled): void
    {
        $scheduled->update(['currently_running' => false]);

        event(new UpdateScheduledEvents($this->eventSchedulerService->fetchEvents()));
    }
}
