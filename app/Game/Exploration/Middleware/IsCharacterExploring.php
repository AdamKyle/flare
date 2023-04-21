<?php

namespace App\Game\Exploration\Middleware;

use App\Flare\Values\AutomationType;
use App\Game\Messages\Events\ServerMessageEvent;
use Closure;

class IsCharacterExploring
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
        $isTooBusy = auth()->user()->character->currentAutomations->where('type', AutomationType::EXPLORING)->isNotEmpty();

        if ($request->wantsJson()) {
            if ($isTooBusy) {

                event(new ServerMessageEvent(auth()->user(), 'No. You\'re too busy. (Are you auto battling? If so, stop. Then engage the action you want. You can still sell items from your inventory.)'));

                return response()->json([
                    'message' => 'You are too busy to do that. You are currently exploring. You can still sell items from your inventory.',
                ], 422);
            }
        } else {
            if ($isTooBusy) {

                event(new ServerMessageEvent(auth()->user(), 'No. You\'re too busy. (Are you exploring? Stop exploring and attempt the action again. You can still sell items from your inventory.)'));

                return redirect()->route('game')->with('error', 'You are too busy to do that. You are currently exploring.');
            }
        }

        return $next($request);
    }
}
