<?php

namespace App\Game\Kingdoms\Middleware;

use Closure;
use Illuminate\Http\Request;

class DoesKingdomBelongToAuthorizedUser
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        $kingdom = $request->route('kingdom');

        if (is_null($kingdom)) {
            $building = $request->route('building');

            if (! is_null($building)) {
                $kingdom = $building->kingdom;
            }
        }

        if (is_null($kingdom)) {
            $kingdomBuilding = $request->route('kingdomBuilding');

            if (! is_null($kingdomBuilding)) {
                $kingdom = $kingdomBuilding->kingdom;
            }
        }

        $character = $request->route('character');
        $message = null;

        if (! is_null($kingdom)) {

            // Character was passed in with the kingdom:
            if (! is_null($character)) {
                if (auth()->user()->character->id !== $kingdom->character_id) {
                    $message = 'Nope. Not allowed to do that.';
                }
            } else {
                // No character was passed in:
                if (auth()->user()->character->id !== $kingdom->character_id) {
                    $message = 'Nope. Not allowed to do that.';
                }
            }

            // Do something with the message:
            if (! is_null($message)) {
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
