<?php

namespace App\Game\Core\Controllers\Api;

use App\Flare\Models\Character;
use App\Game\Exploration\Events\ExplorationTimeOut;
use App\Game\Maps\Events\ShowTimeOutEvent;
use App\Http\Controllers\Controller;

class TimersController extends Controller {

    public function updateTimersForCharacter(Character $character) {
        $characterAutomation = $character->currentAutomations()->first();

        if ($characterAutomation) {
            event(new ExplorationTimeOut($character->user, now()->diffInSeconds($characterAutomation->completed_at)));
        }

        if (!is_null($character->can_move_again_at)) {
            event(new ShowTimeOutEvent($character->user, true, false, now()->diffInSeconds($character->can_move_again_at)));
        }

        return response()->json();
    }
}
