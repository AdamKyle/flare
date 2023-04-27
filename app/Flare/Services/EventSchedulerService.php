<?php

namespace App\Flare\Services;

use App\Flare\Events\UpdateScheduledEvents;
use App\Flare\Models\ScheduledEvent;
use App\Game\Core\Traits\ResponseBuilder;

class EventSchedulerService {

    use ResponseBuilder;

    public function fetchEvents(): array {
        return ScheduledEvent::all()->transform(function($event) {
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

        ScheduledEvent::create([
            'event_type'  => $params['selected_event_type'],
            'raid_id'     => $params['selected_raid'],
            'start_date'  => $params['selected_start_date'],
            'end_date'    => $params['selected_end_date'],
            'description' => $params['event_description'],
        ]);

        event(new UpdateScheduledEvents($this->fetchEvents()));

        return $this->successResult();
    }

    public function updateEvent(array $params, ScheduledEvent $scheduledEvent): array {
        $scheduledEvent->update([
            'event_type'  => $params['selected_event_type'],
            'raid_id'     => $params['selected_raid'],
            'start_date'  => $params['selected_start_date'],
            'end_date'    => $params['selected_end_date'],
            'description' => $params['event_description'],
        ]);

        event(new UpdateScheduledEvents($this->fetchEvents()));

        return $this->successResult();
    }

    public function deleteEvent(int $eventId): array {
        $foundEvent = ScheduledEvent::find($eventId);

        if (is_null($foundEvent)) {
            return $this->errorResult('No event found for deletion.');
        }

        $foundEvent->delete();

        event(new UpdateScheduledEvents($this->fetchEvents()));

        return $this->successResult();
    }

}
