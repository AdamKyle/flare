<?php

namespace App\Game\Kingdoms\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
                if (! is_null($request->route('building'))) {
                    $building = $request->route('building');
                    $message = 'You do not own this kingdom building.';
                    Log::channel('capital_city_building_upgrades')->warning('Kingdom building upgrade rejected.', [
                        'reason' => 'ownership_mismatch',
                        'message' => $message,
                        'character_id' => $character?->id,
                        'kingdom_id' => $kingdom->id,
                        'building_id' => $building->id,
                        'building_name' => $building->name,
                        'building_level' => $building->level,
                    ]);
                }

                if ($request->wantsJson()) {
                    if (is_null($request->route('building'))) {
                        return response()->json([
                            'error' => $message,
                        ], 422);
                    }

                    return response()->json([
                        'message' => $message,
                        'reason' => 'ownership_mismatch',
                    ], 422);
                } else {
                    return redirect()->route('game')->with('error', $message);
                }
            }
        }

        return $next($request);
    }
}
