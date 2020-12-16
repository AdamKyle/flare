<?php

namespace App\Game\Core\Middleware;

use Closure;
use App\Flare\Models\Location;

class IsCharacterAtLocationMiddleware
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
        
        $character = auth()->user()->character;

        $location = Location::where('x', $character->map->character_position_x)->where('y', $character->map->character_position_y)->first();
        
        if (is_null($location)) {
            return redirect()->route('game')->with('error', 'You must first travel to a port to access the market board. Ports are blue ship icons on the map.');
        }

        if (!$location->is_port) {
            return redirect()->route('game')->with('error', 'You must first travel to a port to access the market board. Ports are blue ship icons on the map.');
        }

        return $next($request);
    }
}
