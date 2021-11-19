<?php

namespace App\Game\Automation\Middleware;

use App\Game\Automation\Values\AutomationType;
use Closure;

class IsCharacterInAttackAutomation
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        $isTooBusy = auth()->user()->character->currentAutomations->where('type', AutomationType::ATTACK)->isNotEmpty();

        if ($request->wantsJson()) {
            if ($isTooBusy) {
                return response()->json([
                    'error' => 'You are too busy to do that. You are currently auto attacking.',
                ], 422);
            }
        } else {
            if ($isTooBusy) {
                return redirect()->route('game')->with('error', 'You are too busy to do that. You are currently auto attacking.');
            }
        }

        return $next($request);
    }
}
