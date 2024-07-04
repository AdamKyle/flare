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

        $type = (new EventType($request->event_type));

        $event = ScheduledEvent::where('event_type', $request->event_type)->first();

        if ($type->isDelusionalMemoriesEvent()) {
            return view('events.delusional-memories-event.event-page', [
                'event' => $event,
            ]);
        }

        return redirect()->to(route('welcome'));
    }
}
