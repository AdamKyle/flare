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
        if (! auth()->user()->hasRole('Admin')) {
            return redirect()->back()->with('error', 'You don\'t have permission to view that.');
        }

        return $next($request);
    }
}
