<?php

namespace App\Flare\Middleware;

use App\Admin\Events\RefreshUserScreenEvent;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IsCharacterLoggedInMiddleware
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
        if (! Auth::check()) {

            return event(new RefreshUserScreenEvent(auth()->user()));
        }

        return $next($request);
    }
}
