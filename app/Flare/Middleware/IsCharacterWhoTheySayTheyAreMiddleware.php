<?php

namespace App\Flare\Middleware;

use Closure;

class IsCharacterWhoTheySayTheyAreMiddleware {

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

        $character = $request->route('character');
        $canAccess = true;

        if (!is_null($character)) {
            if (auth()->user()->character->id !== $character->id) {
                $canAccess = false;
            }
        }

        if ($request->wantsJson()) {
            if (!$canAccess) {
                return response()->json([
                    'error' => 'You don\'t have permission to do that.',
                ], 422);
            }
        } else if (!$canAccess) {
            return redirect()->route('game')->with('error', 'You don\'t have permission to do that.');
        }

        return $next($request);
    }
}
