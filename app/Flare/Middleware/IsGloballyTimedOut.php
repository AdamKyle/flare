<?php

namespace App\Flare\Middleware;

use Closure;
use Illuminate\Support\Facades\Route;

class IsGloballyTimedOut
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
        if (!is_null(auth()->user()->timeout_until)) {
            if ($request->wantsJson()) {
                return response()->json([], 422);
            }

            if (Route::current()->getName() !== 'game') {
                return redirect()->route('game')->with('error', 'You are timed out. You cannot do that.');
            }
        }

        return $next($request);
    }
}
