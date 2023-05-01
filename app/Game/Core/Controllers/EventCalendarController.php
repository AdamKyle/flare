<?php

namespace App\Game\Core\Controllers;

use App\Http\Controllers\Controller;

class EventCalendarController extends Controller {

    public function index() {
        return view ('game.events.calendar');
    }
}
