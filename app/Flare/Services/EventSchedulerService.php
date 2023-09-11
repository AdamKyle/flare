<?php

namespace App\Flare\Services;

use App\Flare\Events\UpdateScheduledEvents;
use App\Flare\Models\ScheduledEvent;
use App\Flare\Values\EventType;
use App\Game\Core\Traits\ResponseBuilder;
use Carbon\Carbon;

class EventSchedulerService {

    use ResponseBuilder;

    public function fetchEvents(): array {
        return ScheduledEvent::all()->transform(function ($event) {
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

    public function createMultipleEvents(array $params): void {
        $eventData = [
            'event_type' => $params['selected_event_type'],
        ];

        $date = new Carbon($params['selected_start_date'], config('app.timezone'));

        $eventData['start_date'] = $params['selected_start_date'];
        $eventData['end_date'] = $date->clone()->addDay();

        $eventType = new EventType($params['selected_event_type']);

        $eventData['description'] = $this->eventDescriptionForEventType($eventType);

        ScheduledEvent::create($eventData);

        $this->createEvents($eventData, $params['event_generation_times'], $params['generate_every']);
    }

    protected function eventDescriptionForEventType(EventType $type): string {

        if ($type->isWeeklyCelestials()) {
            return 'During this time, for 24 hours, Celestials will spawn at 80% when ever
            a character moves on any map using any movement option such as teleport and movement actions.
            Celestials are great for Shards which is used in Alchemy to craft powerful useable items!';
        }

        if ($type->isWeeklyCurrencyDrops()) {
            return 'During this time for 24 hours currency rewards will be even more then you are use too! All you have to do is
            fight monsters, head down to Purgatory Dungeons for Copper Coins or even disenchant items! It\'s raining currency!';
        }

        if ($type->isMonthlyPVP()) {
            return 'Once per month, the gates will open to the Colosseum and players can choose to participate in monthly pvp where players
            Compete against each other and the last person standing wins a mythic! Afterwords the Celestial Kings will spawn and they even have a chance
            to drop mythics! Players can enrol in PVP from the action section roughly 8 hours before the actual event.';
        }
    }

    protected function createEvents(array $eventData, int $amount, string $type): void {
        $date = new Carbon($eventData['start_date'], config('app.timezone'));

        for ($i = 1; $i <= $amount; $i++) {

            if ($type === 'weekly') {
                $date = $date->clone()->addWeek();

                $eventData['start_date'] = $date;
                $eventDate['end_date']   = $date->clone()->addDay();
            }

            if ($type === 'monthly') {
                $date = $date->clone()->addMonth();

                $eventData['start_date'] = $date;
                $eventDate['end_date']   = $date->clone()->addDay();
            }

            ScheduledEvent::create($eventData);
        }
    }
}
