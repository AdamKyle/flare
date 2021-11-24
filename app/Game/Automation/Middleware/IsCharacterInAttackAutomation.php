<?php

namespace App\Game\Automation\Middleware;

use App\Game\Automation\Values\AutomationType;
use App\Game\Messages\Events\ServerMessageEvent;
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

                event(new ServerMessageEvent(auth()->user(), 'No. You\'re too busy. (Are you auto battling? If so, stop. Then engage the action you want.)'));

                return response()->json([
                    'error' => 'You are too busy to do that. You are currently auto attacking.',
                ], 422);
            }
        } else {
            if ($isTooBusy) {

                event(new ServerMessageEvent(auth()->user(), 'No. You\'re too busy. (Are you auto battling? If so, stop. Then engage the action you want.)'));

                return redirect()->route('game')->with('error', 'You are too busy to do that. You are currently auto attacking.');
            }
        }

        return $next($request);
    }
}
