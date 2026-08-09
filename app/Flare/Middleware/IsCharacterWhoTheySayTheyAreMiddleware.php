<?php

namespace App\Flare\Middleware;

use App\Flare\Models\Character;
use App\Flare\Models\User;
use Closure;
use Illuminate\Http\Request;

class IsCharacterWhoTheySayTheyAreMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
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

        $user = $request->route('user');
        $canAccess = true;

        $characterId = $character instanceof Character ? $character->id : $character;
        $userId = $user instanceof User ? $user->id : $user;

        if (! is_null($characterId)) {
            if ((int) auth()->user()->character->id !== (int) $characterId) {
                $canAccess = false;
            }
        }

        if (! is_null($userId)) {
            if ((int) auth()->user()->id !== (int) $userId) {
                $canAccess = false;
            }
        }

        if ($request->wantsJson()) {
            if (! $canAccess) {
                return response()->json([
                    'error' => 'You don\'t have permission to do that.',
                ], 422);
            }
        } elseif (! $canAccess) {
            return redirect()->route('game')->with('error', 'You don\'t have permission to do that.');
        }

        return $next($request);
    }
}
