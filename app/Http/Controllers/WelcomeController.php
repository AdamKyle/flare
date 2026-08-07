<?php

namespace App\Http\Controllers;

use App\Flare\Models\ScheduledEvent;
use App\Game\Events\Values\EventType;
use App\Game\Raids\Values\RaidType;
use App\Http\Request\EventPageRequest;
use Auth;

class WelcomeController extends Controller
{
    private const RAID_VIEWS = [
        'jester-of-time-raid' => 'events.jester-of-time-raid.event-page',
        'the-smugglers-are-back-raid' => 'events.the-smugglers-are-back-raid.event-page',
        'ice-queen-raid' => 'events.ice-queen-raid.event-page',
        'the-frozen-king-raid' => 'events.frozen-king-raid.event-page',
        'corrupted-bishop-raid' => 'events.corrupted-bishop-raid.event-page',
        'labyrinth-monster-raid' => 'events.labyrinth-monster-raid.event-page',
    ];

    private const RAID_TYPES = [
        'jester-of-time-raid' => RaidType::JESTER_OF_TIME,
        'the-smugglers-are-back-raid' => RaidType::PIRATE_LORD,
        'ice-queen-raid' => RaidType::ICE_QUEEN,
        'the-frozen-king-raid' => RaidType::FROZEN_KING,
        'corrupted-bishop-raid' => RaidType::CORRUPTED_BISHOP,
        'labyrinth-monster-raid' => RaidType::ENRAGED_LITTLE_GIRL,
    ];

    private const EVENT_VIEWS = [
        'delusional-memories' => 'events.delusional-memories-event.event-page',
        'the-winter-event' => 'events.the-winter-event.event-page',
        'weekly-celestials' => 'events.weekly-celestials-event.event-page',
        'weekly-currency-drops' => 'events.weekly-currency-drops-event.event-page',
        'weekly-faction-loyalty' => 'events.weekly-faction-loyalty-event.event-page',
    ];

    private const EVENT_TYPES = [
        'delusional-memories' => EventType::DELUSIONAL_MEMORIES_EVENT,
        'the-winter-event' => EventType::WINTER_EVENT,
        'weekly-celestials' => EventType::WEEKLY_CELESTIALS,
        'weekly-currency-drops' => EventType::WEEKLY_CURRENCY_DROPS,
        'weekly-faction-loyalty' => EventType::WEEKLY_FACTION_LOYALTY_EVENT,
    ];

    public function welcome()
    {

        $scheduledEvents = ScheduledEvent::where('currently_running', true)->get();

        if (! Auth::check()) {
            return view('welcome', [
                'scheduledEventsRunning' => $scheduledEvents,
            ]);
        }

        if (auth()->user()->hasRole('Admin')) {
            return redirect()->route('home');
        }

        return redirect()->route('game');
    }

    public function showEventCalendar()
    {
        return view('event-calendar');
    }

    public function showEventPage(EventPageRequest $request)
    {
        $eventType = $request->event_type;

        if (array_key_exists($eventType, self::RAID_VIEWS)) {
            return view(self::RAID_VIEWS[$eventType], [
                'event' => $this->findRaidEvent(self::RAID_TYPES[$eventType]),
            ]);
        }

        if (array_key_exists($eventType, self::EVENT_VIEWS)) {
            return view(self::EVENT_VIEWS[$eventType], [
                'event' => $this->findScheduledEvent(self::EVENT_TYPES[$eventType]),
            ]);
        }

        return redirect()->to(route('welcome'));
    }

    private function findRaidEvent(string $raidType): ?ScheduledEvent
    {
        $event = ScheduledEvent::where('event_type', EventType::RAID_EVENT)->where('currently_running', true)->whereHas('raid', function ($query) use ($raidType) {
            return $query->where('raid_type', $raidType);
        })->first();

        if (! is_null($event)) {
            return $event;
        }

        return ScheduledEvent::where('event_type', EventType::RAID_EVENT)->where('start_date', '>=', now())->whereHas('raid', function ($query) use ($raidType) {
            return $query->where('raid_type', $raidType);
        })->orderBy('id')->first();
    }

    private function findScheduledEvent(int $eventType): ?ScheduledEvent
    {
        $scheduleEvent = ScheduledEvent::where('event_type', $eventType)->where('currently_running', true)->first();

        if (is_null($scheduleEvent)) {
            return ScheduledEvent::where('event_type', $eventType)->where('start_date', '>=', now())->orderBy('id')->first();
        }

        return $scheduleEvent;
    }
}
