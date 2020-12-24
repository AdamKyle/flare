<?php

namespace App\Game\Core\Middleware;

use Closure;
use App\Flare\Models\Location;

class IsCharacterWhoTheySayTheyAre
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

        $requestCharacter = $request->character;
        
        if ($request->wantsJson()) {
            if ($character->id !== $requestCharacter->id) {
                return response()->json([
                    'error' => 'You are not allowed to do that.',
                ], 422);
            }
        }
        
        if ($character->id !== $requestCharacter->id) {
            return redirect()->route('game')->with('error', 'You are not allowed to do that.');
        }

        return $next($request);
    }
}
