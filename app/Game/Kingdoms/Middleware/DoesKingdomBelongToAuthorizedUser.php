<?php

namespace App\Game\Kingdoms\Middleware;

use App\Flare\Values\AutomationType;
use App\Game\Messages\Events\ServerMessageEvent;
use Closure;

class DoesKingdomBelongToAuthorizedUser
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
        $kingdom   = $request->has('kingdom') ? $request->kingdom : null;
        $character = $request->has('character') ? $request->character : null;
        $message   = null;

        if (!is_null($kingdom)) {

            // Character was passed in with the kingdom:
            if (!is_null($character)) {
                if ($character->id !== $kingdom->character->id) {
                    $message = 'Nope. Not allowed to do that.';
                }
            } else {
                // No character was passed in:
                if (auth()->user()->character->id !== $kingdom->id) {
                    $message = 'Nope. Not allowed to do that.';
                }
            }

            // Do something with the message:
            if (!is_null($message)) {
                if ($request->wantsJson()) {
                    return response()->json([
                        'error' => $message,
                    ], 422);
                } else {
                    return redirect()->route('game')->with('error', $message);
                }
            }
        }


        return $next($request);
    }
}
