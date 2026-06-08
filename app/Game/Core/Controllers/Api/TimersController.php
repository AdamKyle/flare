<?php

namespace App\Game\Core\Controllers\Api;

use App\Flare\Models\Character;
use App\Game\Automation\Events\AutomationTimeOut;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Core\Events\ShowCraftingTimeOutEvent;
use App\Game\Core\Events\ShowTimeOutEvent as EventsShowTimeOutEvent;
use App\Game\Maps\Events\ShowTimeOutEvent;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class TimersController extends Controller
{
    public function updateTimersForCharacter(Character $character): JsonResponse
    {
        $now = now();

        $characterAutomation = $character->currentAutomations()
            ->where('completed_at', '>', $now)
            ->orderByDesc('started_at')
            ->orderByDesc('id')
            ->first();

        event(new AutomationTimeOut($character->user, $characterAutomation ? $now->diffInSeconds($characterAutomation->completed_at) : 0));

        if (! is_null($character->can_move_again_at)) {
            event(new ShowTimeOutEvent($character->user, true, false, now()->diffInSeconds($character->can_move_again_at)));
        }

        if (! is_null($character->can_craft_again_at)) {
            event(new ShowCraftingTimeOutEvent($character->user, now()->diffInSeconds($character->can_craft_again_at)));
        }

        if (! is_null($character->can_attack_again_at)) {
            event(new EventsShowTimeOutEvent($character->user, now()->diffInSeconds($character->can_attack_again_at)));
        }

        event(new UpdateCharacterStatus($character));

        return response()->json();
    }
}
