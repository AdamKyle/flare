<?php

namespace  App\Admin\Controllers;

use App\Http\Controllers\Controller;

class EventScheduleController extends Controller {

    public function index() {
        return view('admin.events.event-schedule');
    }
}
