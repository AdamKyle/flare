<?php

namespace App\Game\Core\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class IsCharacterAdventuringMiddleware
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

        if (auth()->user()->hasRole('Admin')) {
            return $next($request);
        }
        
        $isAdventuring = auth()->user()->character->adventureLogs->where('in_progress', true)->isNotEmpty();

        if ($request->wantsJson()) {
            if ($isAdventuring) {
                return response()->json([
                    'error' => 'You are adventuring, you cannot do that.',
                ], 422);
            }
        } else {
            if ($isAdventuring) {
                return redirect()->route('game')->with('error', 'You are adventuring, you cannot do that.');
            }
        }

        return $next($request);
    }
}
