<?php

namespace App\Flare\Middleware;

use App\Flare\Models\UserLoginDuration;
use Closure;
use Illuminate\Support\Facades\Auth;

class UpdatePlayerSessionActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {

        if (Auth::check()) {

            $foundLoginDetails = UserLoginDuration::where('user_id', Auth::id())->whereNull('duration_in_seconds')->latest()->first();

            if (is_null($foundLoginDetails)) {
                return $next($request);
            }

            $foundLoginDetails->update([
                'last_activity' => now(),
            ]);
        }

        return $next($request);
    }
}
