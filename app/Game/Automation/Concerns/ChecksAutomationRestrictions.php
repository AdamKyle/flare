<?php

namespace App\Game\Automation\Concerns;

use App\Flare\Models\Character;
use App\Flare\Models\Location;
use App\Game\Automation\Services\AutomationRestrictionService;
use App\Game\Messages\Events\ServerMessageEvent;
use Illuminate\Http\JsonResponse;

trait ChecksAutomationRestrictions
{
    protected function automationRestriction(Character $character, string $action, ?Location $destinationLocation = null): ?array
    {
        return resolve(AutomationRestrictionService::class)->blockedContext($character, $action, $destinationLocation);
    }

    protected function automationRestrictionJsonResponse(Character $character, string $action, ?Location $destinationLocation = null): ?JsonResponse
    {
        $restriction = $this->automationRestriction($character, $action, $destinationLocation);

        if (is_null($restriction)) {
            return null;
        }

        return response()->json([
            'message' => $restriction['message'],
        ], 422);
    }

    protected function automationRestrictionErrorResult(Character $character, string $action, ?Location $destinationLocation = null): ?array
    {
        $restriction = $this->automationRestriction($character, $action, $destinationLocation);

        if (is_null($restriction)) {
            return null;
        }

        return [
            'message' => $restriction['message'],
            'status' => 422,
        ];
    }

    protected function sendAutomationRestrictionMessage(Character $character, string $action, ?Location $destinationLocation = null): bool
    {
        $restriction = $this->automationRestriction($character, $action, $destinationLocation);

        if (is_null($restriction)) {
            return false;
        }

        event(new ServerMessageEvent($character->user, $restriction['message']));

        return true;
    }
}
