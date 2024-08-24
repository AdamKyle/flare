<?php

namespace App\Game\Events\Controllers\Api;

use App\Flare\Models\Character;
use App\Game\Events\Services\EventGoalsService;
use App\Http\Controllers\Controller;

class EventGoalsController extends Controller
{
    private EventGoalsService $eventGoalsService;

    public function __construct(EventGoalsService $eventGoalsService)
    {
        $this->eventGoalsService = $eventGoalsService;
    }

    public function getGlobalEventGoal(Character $character)
    {
        return response()->json($this->eventGoalsService->fetchCurrentEventGoal($character));
    }
}
