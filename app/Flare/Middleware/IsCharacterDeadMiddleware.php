<?php

namespace App\Flare\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class IsCharacterDeadMiddleware
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
        if (auth()->user()->character->is_dead) {
            return redirect()->route('game')->with('error', 'You are dead and must revive before trying to do that. Dead people can\'t do things.');
        }

        return $next($request);
    }
}
