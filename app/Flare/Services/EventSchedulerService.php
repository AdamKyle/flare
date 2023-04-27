<?php

namespace App\Flare\Services;

use App\Flare\Events\UpdateScheduledEvents;
use App\Flare\Models\Raid;
use App\Flare\Models\ScheduledEvents;
use App\Game\Core\Traits\ResponseBuilder;

class EventSchedulerService {

    use ResponseBuilder;

    public function fetchEvents(): array {
        return ScheduledEvents::all()->transform(function($event) {
            $event->event_id    = $event->id;
            $event->title       = $event->getTitleOfEvent();
            $event->start       = $event->start_date;
            $event->end         = $event->end_date;
            $event->description = nl2br($event->description);

            return $event;
        })->toArray();
    }

    public function createEvent(array $params): array {

        if (is_null($params['selected_raid'])) {
            return $this->errorResult('You have an event type selected but not what kind of event (raid or general event).');
        }

        ScheduledEvents::create([
            'event_type'  => $params['selected_event_type'],
            'raid_id'     => $params['selected_raid'],
            'start_date'  => $params['selected_start_date'],
            'end_date'    => $params['selected_end_date'],
            'description' => $params['event_description'],
        ]);

        event(new UpdateScheduledEvents($this->fetchEvents()));

        return $this->successResult();
    }
}
