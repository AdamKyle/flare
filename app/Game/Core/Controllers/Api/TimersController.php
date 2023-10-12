<?php

namespace App\Game\Core\Controllers\Api;

use App\Flare\Models\Character;
use App\Game\Exploration\Events\ExplorationTimeOut;
use App\Http\Controllers\Controller;

class TimersController extends Controller {

    public function updateTimersForCharacter(Character $character) {
        $characterAutomation = $character->currentAutomations()->first();

        if ($characterAutomation) {
            event(new ExplorationTimeOut($character->user, now()->diffInSeconds($characterAutomation->completed_at)));
        }

        return response()->json();
    }
}
