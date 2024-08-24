<?php

namespace App\Flare\Middleware;

use App\Flare\Models\UserLoginDuration;
use Illuminate\Support\Facades\Auth;
use Closure;

class UpdatePlayerSessionActivity {

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {

        if (Auth::check()) {

            $foundLoginDetails = UserLoginDuration::where('user_id', Auth::id())->whereNull('duration_in_seconds')->first();

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
