<?php

namespace App\Flare\Middleware;

use Illuminate\Support\Facades\Auth;
use App\Admin\Events\RefreshUserScreenEvent;
use Closure;

class IsCharacterLoggedInMiddleware
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
        if (!Auth::check()) {

            return event(new RefreshUserScreenEvent(auth()->user()));
        }

        return $next($request);
    }
}
