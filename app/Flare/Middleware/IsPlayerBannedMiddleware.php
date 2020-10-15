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


        $until = !is_null($user->unbanned_at) ?  $user->unbanned_at->format('l jS \\of F Y h:i:s A') . ' ' . $user->unbanned_at->timezoneName. '.' : 'For ever.';

        if ($request->wantsJson() && $user->is_banned) {
            return response()->json([
                'error' => 'You have been banned until: ' . $until,
            ], 422);
        } else if ($user->is_banned) {
            Auth::logout();
            
            return redirect()->to('/')->with('error', 'You have been banned until: ' . $until);
        }

        return $next($request);
    }
}
