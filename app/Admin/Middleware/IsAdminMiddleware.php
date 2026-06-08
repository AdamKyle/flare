<?php

namespace App\Admin\Middleware;

use Closure;

class IsAdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        $user = $request->user();

        if (is_null($user)) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return redirect()->route('login');
        }

        if (! $user->hasRole('Admin')) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Forbidden.'], 403);
            }

            return redirect()->back()->with('error', 'You don\'t have permission to view that.');
        }

        return $next($request);
    }
}
