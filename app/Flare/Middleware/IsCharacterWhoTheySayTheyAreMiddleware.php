<?php

namespace App\Flare\Middleware;

use App\Flare\Models\Character;
use Closure;

class IsCharacterWhoTheySayTheyAreMiddleware {

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
        if (is_null(auth()->user())) {
            return redirect()->route('game')->with('error', 'You don\'t have permission to do that.');
        }

        if (auth()->user()->hasRole('Admin')) {
            $letAdminThrough = [
                'game.inventory.compare',
                'game.inventory.compare-items',
                'game.equip.item',
                'game.inventory.unequip',
                'skill.character.info',
                'market.history',
            ];

            if (in_array($request->route()->getName(), $letAdminThrough)) {
                return $next($request);
            }

            return redirect()->route('home');
        }

        $character = $request->route('character');

        $user      = $request->route('user');
        $canAccess = true;

        // REMINDER: Chances are the method (on the controller) does not exist if you need to uncomment this.
        // or, you forgot to pass character to the controller action
//        if (is_string($character)) {
//            $character = Character::find($character);
//            dump($character);
//        }

        if (!is_null($character)) {
            if (auth()->user()->character->id !== $character->id) {
                $canAccess = false;
            }
        }

        if (!is_null($user)) {
            if (auth()->user()->id !== $user->id) {
                $canAccess = false;
            }
        }

        if ($request->wantsJson()) {
            if (!$canAccess) {
                return response()->json([
                    'error' => 'You don\'t have permission to do that.',
                ], 422);
            }
        } else if (!$canAccess) {
            return redirect()->route('game')->with('error', 'You don\'t have permission to do that.');
        }

        return $next($request);
    }
}
