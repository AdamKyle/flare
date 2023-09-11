<?php

namespace App\Admin\Controllers\Api;

use App\Admin\Requests\CreateMultipleEventsRequest;
use App\Admin\Requests\DeleteEventRequest;
use App\Flare\Models\Raid;
use App\Flare\Models\ScheduledEvent;
use App\Flare\Services\EventSchedulerService;
use App\Http\Controllers\Controller;
use App\Admin\Requests\ManageEventRequest;
use App\Flare\Values\EventType;

class EventScheduleController extends Controller {

    private EventSchedulerService $eventSchedulerService;

    public function __construct(EventSchedulerService $eventSchedulerService) {
        $this->eventSchedulerService = $eventSchedulerService;
    }


    public function index() {
        $raids = Raid::select('name', 'id')->get()->toArray();

        return response()->json([
            'raids'       => $raids,
            'events'      => $this->eventSchedulerService->fetchEvents(),
            'event_types' => EventType::getOptionsForSelect(),
        ]);
    }

    public function createEvent(ManageEventRequest $request) {
        $result = $this->eventSchedulerService->createEvent($request->all());

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    public function updateEvent(ManageEventRequest $request, ScheduledEvent $scheduledEvent) {
        $result = $this->eventSchedulerService->updateEvent($request->all(), $scheduledEvent);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    public function deleteEvent(DeleteEventRequest $request) {
        $result = $this->eventSchedulerService->deleteEvent($request->event_id);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    public function createMultipleEvents(CreateMultipleEventsRequest $request) {
        $this->eventSchedulerService->createMultipleEvents($request->all());

        return response()->json();
    }
}
