<?php

namespace App\Flare\Services;

use App\Flare\Events\UpdateScheduledEvents;
use App\Flare\Models\Announcement;
use App\Flare\Models\Event;
use App\Flare\Models\Raid;
use App\Flare\Models\ScheduledEvent;
use App\Flare\Models\ScheduledEventConfiguration;
use App\Game\Messages\Events\DeleteAnnouncementEvent;
use Facades\App\Game\Core\Handlers\AnnouncementHandler;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Events\Values\EventType;
use Carbon\Carbon;

class EventSchedulerService
{
    use ResponseBuilder;

    const GENERATE_EVENT_AMOUNT = 5;

    public function fetchEvents(): array
    {
        return ScheduledEvent::all()->transform(function ($event) {
            $event->event_id = $event->id;
            $event->title = $event->getTitleOfEvent();
            $event->start = $event->start_date;
            $event->end = $event->end_date;
            $event->description = nl2br($event->description);

            return $event;
        })->toArray();
    }

    public function createEvent(array $params): array
    {
        if (is_null($params['selected_raid']) && $params['selected_event_type'] === EventType::RAID_EVENT) {
            return $this->errorResult('You have an event type selected but not what kind of event (raid or general event).');
        }

        $scheduledEvent = ScheduledEvent::create([
            'event_type' => $params['selected_event_type'],
            'raid_id' => $params['selected_raid'],
            'start_date' => $params['selected_start_date'],
            'end_date' => $params['selected_end_date'],
            'description' => $params['event_description'],
            'raids_for_event' => $params['raids_for_event'],
        ]);

        $this->createRaidEventsForScheduledEventWith($scheduledEvent);

        event(new UpdateScheduledEvents($this->fetchEvents()));

        return $this->successResult();
    }

    private function createRaidEventsForScheduledEventWith(ScheduledEvent $scheduledEvent): void
    {
        $raidsForEvent = $scheduledEvent->raids_for_event;

        foreach ($raidsForEvent as $raidForEvent) {

            $raid = Raid::find($raidForEvent['selected_raid']);

            ScheduledEvent::create([
                'event_type' => EventType::RAID_EVENT,
                'raid_id' => $raidForEvent['selected_raid'],
                'start_date' => $raidForEvent['start_date'],
                'end_date' => $raidForEvent['end_date'],
                'description' => $raid->scheduled_event_description,
            ]);
        }
    }

    public function updateEvent(array $params, ScheduledEvent $scheduledEvent): array
    {
        $scheduledEvent->update([
            'event_type' => $params['selected_event_type'],
            'raid_id' => $params['selected_raid'],
            'start_date' => $params['selected_start_date'],
            'end_date' => $params['selected_end_date'],
            'description' => $params['event_description'],
        ]);

        $scheduledEvent = $scheduledEvent->refresh();

        if ($scheduledEvent->currently_running) {
            $event = Event::where('type', $params['selected_event_type'])->first();

            if (!is_null($event)) {
                $event->update([
                    'ends_at' => $params['selected_end_date']
                ]);

                $announcement = Announcement::where('event_id', $event->id)->first();

                event(new DeleteAnnouncementEvent($announcement->id));

                $announcement->delete();

                $name = AnnouncementHandler::getNameForType($params['selected_event_type']);

                AnnouncementHandler::createAnnouncement($name);
            }
        }

        event(new UpdateScheduledEvents($this->fetchEvents()));

        return $this->successResult();
    }

    public function deleteEvent(int $eventId): array
    {
        $foundEvent = ScheduledEvent::find($eventId);

        if (is_null($foundEvent)) {
            return $this->errorResult('No event found for deletion.');
        }

        $foundEvent->delete();

        event(new UpdateScheduledEvents($this->fetchEvents()));

        return $this->successResult();
    }

    public function createMultipleEvents(array $params): void
    {
        $eventData = $this->createBaseScheduledEvent($params);

        ScheduledEvent::create($eventData);

        $date = $this->createEvents($eventData, self::GENERATE_EVENT_AMOUNT, $params['generate_every']);

        ScheduledEventConfiguration::create([
            'event_type' => $params['selected_event_type'],
            'start_date' => $date,
            'generate_every' => $params['generate_every'],
            'last_time_generated' => now(),
        ]);

        event(new UpdateScheduledEvents($this->fetchEvents()));
    }

    public function generateFutureEvents(ScheduledEventConfiguration $scheduledEventConfiguration): void
    {
        $event = ScheduledEvent::where('event_type', $scheduledEventConfiguration->event_type)
            ->where('start_date', '>=', now())
            ->orderBy('start_date')
            ->get()
            ->last();

        $params = [
            'selected_event_type' => $scheduledEventConfiguration->event_type,
            'selected_start_date' => $event->start_date,
        ];

        $eventData = $this->createBaseScheduledEvent($params);

        $date = $this->createEvents($eventData, self::GENERATE_EVENT_AMOUNT, $scheduledEventConfiguration->generate_every);

        $scheduledEventConfiguration->update([
            'start_date' => $date,
            'last_time_generated' => now(),
        ]);

        event(new UpdateScheduledEvents($this->fetchEvents()));
    }

    private function createBaseScheduledEvent(array $params): array
    {
        $eventData = [
            'event_type' => $params['selected_event_type'],
        ];

        $eventType = new EventType($params['selected_event_type']);

        $date = (new Carbon($params['selected_start_date']))->tz(config('app.timezone'));

        $eventData['start_date'] = $date;

        // If we are monthly pbp, then it always ends at 6pm regardless of when you set the start date.
        $eventData['end_date'] = $date->copy()->addDay();

        $eventData['description'] = $this->eventDescriptionForEventType($eventType);

        return $eventData;
    }

    private function eventDescriptionForEventType(EventType $type): string
    {

        if ($type->isWeeklyCelestials()) {
            return 'During this time, for 24 hours, Celestials will spawn at 80% when ever
            a character moves on any map using any movement option such as teleport and movement actions.
            Celestials are great for Shards which is used in Alchemy to craft powerful useable items!';
        }

        if ($type->isWeeklyCurrencyDrops()) {
            return 'For the next 24 hours you just have to kill creatures for Gold Dust,' .
                'Shards and Copper Coins (provided you have the quest item) will drop at a rate of 1-50 per kill! How fun!';
        }

        if ($type->isWeeklyFactionLoyaltyEvent()) {
            return 'Once per week players can participate in Faction Loyalty Event where they get 2 points i their faction loyalty tasks be they bounty or crafting.
            When a player levels up the fame with an NPC of the faction they are pledged to, the new levels requirements will be halved.';
        }
    }

    private function createEvents(array $eventData, int $amount, string $type): Carbon
    {
        $date = new Carbon($eventData['start_date'], config('app.timezone'));

        for ($i = 1; $i <= $amount; $i++) {
            if ($type === 'weekly') {

                $startDate = $date->copy()->addWeek();

                $endDate = $startDate->copy()->addDay();

                $newEventData = $eventData;
                $newEventData['start_date'] = $startDate;
                $newEventData['end_date'] = $endDate;

                ScheduledEvent::create($newEventData);

                $date = $startDate;
            }

            if ($type === 'monthly') {
                $startDate = $date->copy()->addMonthsNoOverflow()->endOfMonth();

                $endDate = $startDate->setTime(19, 0);

                $newEventData = $eventData;
                $newEventData['start_date'] = $startDate;
                $newEventData['end_date'] = $endDate;

                ScheduledEvent::create($newEventData);

                $date = $startDate;
            }
        }

        return $date;
    }
}
