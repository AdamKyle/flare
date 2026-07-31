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
        if (! Auth::check() || Auth::user()->hasRole('Admin')) {
            return $next($request);
        }

        $foundLoginDetails = UserLoginDuration::where('user_id', Auth::id())
            ->whereNull('logged_out_at')
            ->whereNull('duration_in_seconds')
            ->latest('logged_in_at')
            ->first();

        if (is_null($foundLoginDetails)) {
            return $next($request);
        }

        $now = now();
        $lastActivity = collect([
            $foundLoginDetails->last_activity,
            $foundLoginDetails->last_heart_beat,
        ])->filter()->sortByDesc(fn ($activity) => $activity->getTimestamp())->first() ?? $foundLoginDetails->logged_in_at;

        if ($lastActivity->lt($foundLoginDetails->logged_in_at)) {
            $lastActivity = $foundLoginDetails->logged_in_at;
        }

        if ($lastActivity->gt($now)) {
            $lastActivity = $now;
        }

        $minutesSinceConfirmed = $lastActivity->diffInMinutes($now);
        $sessionLifeTime = (int) config('session.lifetime');

        if ($minutesSinceConfirmed >= $sessionLifeTime) {
            $loggedOutAt = $lastActivity;

            $foundLoginDetails->update([
                'logged_out_at' => $loggedOutAt,
                'duration_in_seconds' => $foundLoginDetails->logged_in_at->diffInSeconds($loggedOutAt),
            ]);

            Auth::logout();

            return $next($request);
        }

        $foundLoginDetails->update([
            'last_activity' => $now,
            'last_heart_beat' => $now,
        ]);

        return $next($request);
    }
}
