<?php

namespace App\Admin\Controllers\Api;

use App\Flare\Models\Raid;
use App\Flare\Services\EventSchedulerService;
use App\Http\Controllers\Controller;
use App\Admin\Requests\CreateEventRequest;

class EventScheduleController extends Controller {

    private EventSchedulerService $eventSchedulerService;

    public function __construct(EventSchedulerService $eventSchedulerService) {
        $this->eventSchedulerService = $eventSchedulerService;
    }


    public function index() {
        $raids = Raid::select('name', 'id')->get()->toArray();

        return response()->json([
            'raids'  => $raids,
            'events' => $this->eventSchedulerService->fetchEvents(),
        ]);
    }

    public function createEvent(CreateEventRequest $request) {
        $result = $this->eventSchedulerService->createEvent($request->all());

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }
}
