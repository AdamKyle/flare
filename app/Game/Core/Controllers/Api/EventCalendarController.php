<?php

namespace App\Game\Core\Controllers\Api;

use App\Flare\Services\EventSchedulerService;
use App\Http\Controllers\Controller;

class EventCalendarController extends Controller {

    private EventSchedulerService $eventSchedulerService;

    public function __construct(EventSchedulerService $eventSchedulerService) {
        $this->eventSchedulerService = $eventSchedulerService;
    }

    public function loadEvents() {
        return response()->json([
            'events' => $this->eventSchedulerService->fetchEvents(),
        ]);
    }
}
