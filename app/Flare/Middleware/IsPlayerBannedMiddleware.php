<?php

namespace App\Flare\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class IsPlayerBannedMiddleware
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
        $user = auth()->user();

        /**
         * User might not be logged in, but looking at a 
         * specific route we said they dont have to be logged in for.
         */
        if (is_null($user)) {
            return $next($request);
        }

        $until = !is_null($user->unbanned_at) ?  $user->unbanned_at->format('l jS \\of F Y h:i:s A') . ' ' . $user->unbanned_at->timezoneName. '.' : 'For ever.';

        if ($user->is_banned) {
            Auth::logout();
            
            return redirect()->to('/')->with('error', 'You have been banned until: ' . $until);
        }

        return $next($request);
    }
}
