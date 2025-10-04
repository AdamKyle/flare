<?php

namespace App\Flare\Middleware;

use App\Flare\Models\UserLoginDuration;
use Closure;
use Illuminate\Support\Facades\Auth;

class TrackSessionLifeMiddleware
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

            $minutesSinceConfirmed = now()->diffInMinutes($foundLoginDetails->last_activity);
            $sessionLifeTime = (int) config('session.lifetime');

            if ($minutesSinceConfirmed >= $sessionLifeTime) {

                $now = now();

                $foundLoginDetails->update([
                    'logged_out_at' => $now,
                    'duration_in_seconds' => $now->diffInSeconds($foundLoginDetails->logged_in_at),
                    'last_heart_beat' => now(),
                ]);

                Auth::logout();

                return $next($request);
            }

            $foundLoginDetails->update([
                'last_heart_beat' => now(),
            ]);
        }

        return $next($request);
    }
}
