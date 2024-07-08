<?php

namespace App\Http\Controllers;


use App\Flare\Models\ScheduledEvent;
use App\Game\Events\Values\EventType;
use App\Http\Request\EventPageRequest;
use Auth;

class WelcomeController extends Controller {

    public function welcome() {

        $scheduledEvents = ScheduledEvent::where('currently_running', true)->get();

        if (!Auth::check()) {
            return view('welcome', [
                'scheduledEventsRunning' => $scheduledEvents,
            ]);
        }

        if (auth()->user()->hasRole('Admin')) {
            return redirect()->route('home');
        }

        return redirect()->route('game');
    }

    public function showEventPage(EventPageRequest $request) {

        $eventType = $request->event_type;
        $raids = ['jester-of-time'];
        $events = ['delusional-memories'];

        if (in_array($eventType, $raids)) {

            $event = ScheduledEvent::where('event_type', EventType::RAID_EVENT)->where('currently_running', true)->first();

            switch($eventType) {
                case 'jester-of-time':
                    return view('events.jester-of-time-raid.event-page', [
                        'event' => $event,
                    ]);
                default:
                    return redirect()->to(route('welcome'));
            }
        }


        if (in_array($eventType, $events)) {

            $event = ScheduledEvent::where('event_type', EventType::DELUSIONAL_MEMORIES_EVENT)->where('currently_running', true)->first();

            switch($eventType) {
                case 'delusional-memories':
                    return view('events.delusional-memories-event.event-page', [
                        'event' => $event,
                    ]);
                default:
                    return redirect()->to(route('welcome'));
            }
        }

        return redirect()->to(route('welcome'));
    }
}
