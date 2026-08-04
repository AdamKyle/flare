<?php

namespace App\Game\Events\Services;

use App\Flare\Events\UpdateScheduledEvents;
use App\Flare\Models\Announcement;
use App\Flare\Models\Event;
use App\Flare\Models\Raid;
use App\Flare\Models\ScheduledEvent;
use App\Flare\Models\ScheduledEventConfiguration;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Events\Values\EventType;
use App\Game\Events\Values\ScheduledEventStatus;
use App\Game\Messages\Events\DeleteAnnouncementEvent;
use App\Game\Raids\Services\RaidMapConflictService;
use App\Game\Raids\Values\RaidType;
use Carbon\Carbon;
use Facades\App\Game\Core\Handlers\AnnouncementHandler;

class EventSchedulerService
{
    use ResponseBuilder;

    const GENERATE_EVENT_AMOUNT = 5;

    public function __construct(
        private readonly RaidMapConflictService $raidMapConflictService,
    ) {}

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

        event(new UpdateScheduledEvents($this->fetchEvents()));

        return $this->successResult();
    }

    /**
     * Create (or reuse) the raid-event children described by the parent
     * schedule's raids_for_event payload, linking each to the parent via
     * parent_scheduled_event_id. Idempotent: an existing child for the same
     * parent/raid/start (such as one already created by the development
     * command) is reused rather than duplicated.
     *
     * @return ScheduledEvent[]
     */
    public function createRaidEventsForScheduledEventWith(ScheduledEvent $scheduledEvent): array
    {
        $raidsForEvent = $scheduledEvent->raids_for_event;

        if (is_null($raidsForEvent)) {
            return [];
        }

        $children = [];

        foreach ($raidsForEvent as $raidForEvent) {
            $childStartDate = new Carbon($raidForEvent['start_date']);
            $childEndDate = new Carbon($raidForEvent['end_date']);

            if ($childStartDate < $scheduledEvent->start_date || $childEndDate > $scheduledEvent->end_date) {
                continue;
            }

            $existingChild = ScheduledEvent::query()
                ->where('parent_scheduled_event_id', $scheduledEvent->id)
                ->where('raid_id', $raidForEvent['selected_raid'])
                ->where('start_date', $childStartDate)
                ->first();

            if (! is_null($existingChild)) {
                $children[] = $existingChild;

                continue;
            }

            $raid = Raid::find($raidForEvent['selected_raid']);

            $gameMapIds = $this->raidMapConflictService->mapIdsForRaid($raid);
            $raidIdsOnSameMaps = $this->raidMapConflictService->raidIdsOnMaps($gameMapIds);

            $hasOverlap = ! empty($raidIdsOnSameMaps) && ScheduledEvent::query()
                ->whereIn('raid_id', $raidIdsOnSameMaps)
                ->where('id', '!=', $scheduledEvent->id)
                ->where('start_date', '<', $childEndDate)
                ->where('end_date', '>', $childStartDate)
                ->exists();

            if ($hasOverlap) {
                continue;
            }

            $children[] = ScheduledEvent::create([
                'event_type' => EventType::RAID_EVENT,
                'raid_id' => $raidForEvent['selected_raid'],
                'parent_scheduled_event_id' => $scheduledEvent->id,
                'start_date' => $childStartDate,
                'end_date' => $childEndDate,
                'description' => $raid->scheduled_event_description,
                'status' => ScheduledEventStatus::SCHEDULED,
            ]);
        }

        return $children;
    }

    public function updateEvent(array $params, ScheduledEvent $scheduledEvent): array
    {
        $scheduledEvent->update([
            'event_type' => $params['selected_event_type'],
            'raid_id' => $params['selected_raid'],
            'start_date' => $params['selected_start_date'],
            'end_date' => $params['selected_end_date'],
            'description' => $params['event_description'],
            'raids_for_event' => array_key_exists('raids_for_event', $params) ? $params['raids_for_event'] : $scheduledEvent->raids_for_event,
        ]);

        $scheduledEvent = $scheduledEvent->refresh();

        if ($scheduledEvent->currently_running) {
            $event = Event::where('scheduled_event_id', $scheduledEvent->id)->first();

            if (! is_null($event)) {
                $event->update([
                    'ends_at' => $params['selected_end_date'],
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

    public function generateFutureRaid(ScheduledEvent $scheduledRaidEvent, ?Carbon $futureDate = null): void
    {

        $raid = $scheduledRaidEvent->raid;

        if (in_array($raid->raid_type, [RaidType::CORRUPTED_BISHOP, RaidType::JESTER_OF_TIME, RaidType::FROZEN_KING, RaidType::ICE_QUEEN])) {
            return;
        }

        $startDate = $futureDate ?? $scheduledRaidEvent->start_date->copy()->addMonths(3);
        $endDate = $startDate->copy()->addMonth();

        $raidEvents = ScheduledEvent::query()
            ->where('raid_id', $raid->id)
            ->where('start_date', '<', $endDate)
            ->where('end_date', '>', $startDate)
            ->orderBy('start_date')
            ->get();

        $gameMapIds = $this->raidMapConflictService->mapIdsForRaid($raid);
        $raidIdsOnSameMaps = $this->raidMapConflictService->raidIdsOnMaps($gameMapIds);

        if ($raidEvents->isEmpty()) {
            $hasSameMapRaidInWindow = ! empty($raidIdsOnSameMaps) && ScheduledEvent::query()
                ->whereIn('raid_id', $raidIdsOnSameMaps)
                ->where('start_date', '<', $endDate)
                ->where('end_date', '>', $startDate)
                ->exists();

            if (! $hasSameMapRaidInWindow) {
                $params = [
                    'selected_event_type' => $scheduledRaidEvent->event_type,
                    'selected_start_date' => $startDate,
                    'selected_raid' => $raid->id,
                    'selected_end_date' => $endDate,
                    'event_description' => $scheduledRaidEvent->description,
                    'raids_for_event' => $scheduledRaidEvent->raids_for_event,
                ];

                $this->createEvent($params);

                return;
            }

            $this->generateFutureRaid($scheduledRaidEvent, $endDate->copy());

            return;
        }

        if ($raidEvents->count() > 1) {
            $lastRaidEvent = $raidEvents->last();

            $shiftedLastRaidStartDate = $lastRaidEvent->end_date->copy()->addHour();
            $shiftedLastRaidEndDate = $shiftedLastRaidStartDate->copy()->addMonth();

            $hasRaidInShiftedWindow = ! empty($raidIdsOnSameMaps) && ScheduledEvent::query()
                ->whereIn('raid_id', $raidIdsOnSameMaps)
                ->where('start_date', '<', $shiftedLastRaidEndDate)
                ->where('end_date', '>', $shiftedLastRaidStartDate)
                ->exists();

            if (! $hasRaidInShiftedWindow) {
                $params = [
                    'selected_event_type' => $scheduledRaidEvent->event_type,
                    'selected_start_date' => $shiftedLastRaidStartDate,
                    'selected_raid' => $scheduledRaidEvent->raid_id,
                    'selected_end_date' => $shiftedLastRaidEndDate,
                    'event_description' => $scheduledRaidEvent->description,
                    'raids_for_event' => $scheduledRaidEvent->raids_for_event,
                ];

                $this->createEvent($params);

                return;
            }

            $this->generateFutureRaid($scheduledRaidEvent, $shiftedLastRaidStartDate->copy()->addMonth());

            return;
        }

        $existingRaidEvent = $raidEvents->first();

        $shiftedStartDate = $existingRaidEvent->end_date->copy()->addHour();
        $shiftedEndDate = $shiftedStartDate->copy()->addMonth();

        $hasRaidInShiftedWindow = ! empty($raidIdsOnSameMaps) && ScheduledEvent::query()
            ->whereIn('raid_id', $raidIdsOnSameMaps)
            ->where('start_date', '<', $shiftedEndDate)
            ->where('end_date', '>', $shiftedStartDate)
            ->exists();

        if (! $hasRaidInShiftedWindow) {
            $params = [
                'selected_event_type' => $scheduledRaidEvent->event_type,
                'selected_start_date' => $shiftedStartDate,
                'selected_raid' => $scheduledRaidEvent->raid_id,
                'selected_end_date' => $shiftedEndDate,
                'event_description' => $scheduledRaidEvent->description,
                'raids_for_event' => $scheduledRaidEvent->raids_for_event,
            ];

            $this->createEvent($params);

            return;
        }

        $this->generateFutureRaid($scheduledRaidEvent, $shiftedStartDate->copy()->addMonth());
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
            return 'For the next 24 hours you just have to kill creatures for Gold Dust,'.
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
