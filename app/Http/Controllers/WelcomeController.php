<?php

namespace App\Http\Controllers;


use App\Flare\Models\ScheduledEvent;
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
}
