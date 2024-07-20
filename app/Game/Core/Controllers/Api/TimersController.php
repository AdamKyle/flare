<?php

namespace App\Game\Core\Controllers\Api;

use App\Flare\Models\Character;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Core\Events\ShowCraftingTimeOutEvent;
use App\Game\Core\Events\ShowTimeOutEvent as EventsShowTimeOutEvent;
use App\Game\Exploration\Events\ExplorationTimeOut;
use App\Game\Maps\Events\ShowTimeOutEvent;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class TimersController extends Controller {

    /**
     * @param Character $character
     * @return JsonResponse
     */
    public function updateTimersForCharacter(Character $character): JsonResponse {
        $characterAutomation = $character->currentAutomations()->first();

        if ($characterAutomation) {
            event(new ExplorationTimeOut($character->user, now()->diffInSeconds($characterAutomation->completed_at)));
        }

        if (!is_null($character->can_move_again_at)) {
            event(new ShowTimeOutEvent($character->user, true, false, now()->diffInSeconds($character->can_move_again_at)));
        }

        if (!is_null($character->can_craft_again_at)) {
            event(new ShowCraftingTimeOutEvent($character->user, now()->diffInSeconds($character->can_craft_again_at)));
        }

        if (!is_null($character->can_attack_again_at)) {
            event(new EventsShowTimeOutEvent($character->user, now()->diffInSeconds($character->can_attack_again_at)));
        }

        event(new UpdateCharacterStatus($character));

        return response()->json();
    }
}
