<?php

namespace App\Game\Core\Controllers\Api;

use App\Flare\Models\Character;
use App\Game\Automation\Events\AutomationTimeOut;
use App\Game\Automation\Services\AutomationRestrictionService;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Battle\Services\AttackTimerService;
use App\Game\Core\Events\ShowCraftingTimeOutEvent;
use App\Game\Core\Events\ShowTimeOutEvent as EventsShowTimeOutEvent;
use App\Game\Maps\Events\ShowTimeOutEvent;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class TimersController extends Controller
{
    public function __construct(private readonly ?AttackTimerService $attackTimerService = null) {}

    public function updateTimersForCharacter(Character $character): JsonResponse
    {
        $attackTimerService = $this->attackTimerService ?? new AttackTimerService(new AutomationRestrictionService());
        $character = $attackTimerService->normalizeExpiredAttackTimer($character);

        $characterAutomation = $character->currentAutomations()
            ->where('completed_at', '>', now())
            ->orderByDesc('started_at')
            ->orderByDesc('id')
            ->first();

        event(new AutomationTimeOut($character->user, $characterAutomation ? $this->remainingSeconds($characterAutomation->completed_at) : 0));

        event(new ShowTimeOutEvent($character->user, true, false, $this->remainingSeconds($character->can_move_again_at)));

        event(new ShowCraftingTimeOutEvent($character->user, $this->remainingSeconds($character->can_craft_again_at)));

        event(new EventsShowTimeOutEvent($character->user, $this->remainingSeconds($character->can_attack_again_at)));

        event(new UpdateCharacterStatus($character));

        return response()->json();
    }

    private function remainingSeconds(?Carbon $timestamp): int
    {
        if (is_null($timestamp) || $timestamp->isPast()) {
            return 0;
        }

        return now()->diffInSeconds($timestamp, false);
    }
}
