<?php

namespace App\Http\Controllers;

use App\Flare\Models\ScheduledEvent;
use App\Game\Events\Values\EventType;
use App\Http\Request\EventPageRequest;
use Auth;

class WelcomeController extends Controller
{
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

    public function showEventPage(EventPageRequest $request)
    {

        $eventType = $request->event_type;
        $raids = ['jester-of-time', 'the-smugglers-are-back-raid', 'ice-queen-raid'];
        $events = ['delusional-memories', 'weekly-celestials', 'weekly-currency-drops', 'weekly-faction-loyalty', 'tlessas-feedback-event',];

        if (in_array($eventType, $raids)) {

            $events = ScheduledEvent::where('event_type', EventType::RAID_EVENT)->where('currently_running', true)->get();

            foreach ($events as $event) {
                switch ($eventType) {
                    case 'jester-of-time':
                        return view('events.jester-of-time-raid.event-page', [
                            'event' => $event,
                        ]);
                    case 'the-smugglers-are-back-raid':
                        return view('events.the-smugglers-are-back-raid.event-page', [
                            'event' => $event,
                        ]);
                    case 'ice-queen-raid':
                        return view('events.ice-queen-raid.event-page', [
                            'event' => $event,
                        ]);
                    default:
                        return redirect()->to(route('welcome'));
                }
            }
        }

        if (in_array($eventType, $events)) {

            switch ($eventType) {
                case 'delusional-memories':
                    return view('events.delusional-memories-event.event-page', [
                        'event' => ScheduledEvent::where('event_type', EventType::DELUSIONAL_MEMORIES_EVENT)->where('currently_running', true)->first(),
                    ]);
                case 'weekly-celestials':
                    return view('events.weekly-celestials-event.event-page', [
                        'event' => ScheduledEvent::where('event_type', EventType::WEEKLY_CELESTIALS)->where('currently_running', true)->first(),
                    ]);
                case 'weekly-currency-drops':
                    return view('events.weekly-currency-drops-event.event-page', [
                        'event' => ScheduledEvent::where('event_type', EventType::WEEKLY_CURRENCY_DROPS)->where('currently_running', true)->first(),
                    ]);
                case 'weekly-faction-loyalty':
                    return view('events.weekly-faction-loyalty-event.event-page', [
                        'event' => ScheduledEvent::where('event_type', EventType::WEEKLY_FACTION_LOYALTY_EVENT)->where('currently_running', true)->first(),
                    ]);
                case 'tlessas-feedback-event':
                    return view('events.feedback-event.event-page', [
                        'event' => ScheduledEvent::where('event_type', EventType::FEEDBACK_EVENT)->where('currently_running', true)->first(),
                    ]);
                default:
                    return redirect()->to(route('welcome'));
            }
        }

        return redirect()->to(route('welcome'));
    }
}
