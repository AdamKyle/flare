<?php

namespace App\Game\Kingdoms\Middleware;

use App\Game\Automation\Services\AutomationRestrictionService;
use Closure;
use Illuminate\Http\Request;

class BlocksKingdomAutomationManagement
{
    public function __construct(private readonly AutomationRestrictionService $automationRestrictionService) {}

    public function handle(Request $request, Closure $next)
    {
        $character = $request->user()?->character;

        if (is_null($character)) {
            return $next($request);
        }

        $restriction = $this->automationRestrictionService->blockedContext($character, AutomationRestrictionService::KINGDOM_MANAGEMENT);

        if (! is_null($restriction)) {
            return response()->json(['message' => $restriction['message']], 422);
        }

        return $next($request);
    }
}
