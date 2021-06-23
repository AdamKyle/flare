<?php

namespace App\Game\Market\Middleware;

use Closure;
use App\Flare\Models\Location;

class CanCharacterAccessMarket
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

        if (auth()->user()->hasRole('Admin')) {
            return $next($request);
        }

        $character = auth()->user()->character;

        $location = Location::where('x', $character->map->character_position_x)->where('y', $character->map->character_position_y)->first();


        if ($request->wantsJson()) {
            if (is_null($location)) {
                return response()->json([
                    'error' => 'You must first travel to a port to access the market board. Ports are blue ship icons on the map.',
                ], 422);
            }

            if (!$location->is_port) {
                return response()->json([
                    'error' => 'You must first travel to a port to access the market board. Ports are blue ship icons on the map.',
                ], 422);
            }
        }

        if (is_null($location)) {
            return redirect()->route('game')->with('error', 'You must first travel to a port to access the market board. Ports are blue ship icons on the map.');
        }

        if (!$location->is_port) {
            return redirect()->route('game')->with('error', 'You must first travel to a port to access the market board. Ports are blue ship icons on the map.');
        }

        return $next($request);
    }
}
