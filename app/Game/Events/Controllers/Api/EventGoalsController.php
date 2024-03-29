<?php

namespace App\Game\Events\Controllers\Api;

use App\Flare\Models\Character;
use App\Http\Controllers\Controller;
use App\Game\Events\Services\EventGoalsService;

class EventGoalsController extends Controller {

    private EventGoalsService $eventGoalsService;

    public function __construct(EventGoalsService $eventGoalsService) {
        $this->eventGoalsService = $eventGoalsService;
    }

    public function getGlobalEventGoal(Character $character) {
        return response()->json($this->eventGoalsService->fetchCurrentEventGoal($character));
    }
}
