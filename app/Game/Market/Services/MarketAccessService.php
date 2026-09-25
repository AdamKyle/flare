<?php

namespace App\Game\Market\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Location;
use App\Flare\Models\User;

class MarketAccessService
{
    /**
     * Determine whether the user may use the Market: Admins always may, everyone else's character must stand on a port.
     *
     * @param User $user
     * @return bool
     */
    public function canAccess(User $user): bool
    {
        if ($user->hasRole('Admin')) {
            return true;
        }

        return $this->isAtPort($user->character);
    }

    /**
     * Determine whether the character is standing on a port Location.
     *
     * @param Character $character
     * @return bool
     */
    private function isAtPort(Character $character): bool
    {
        return Location::where('x', $character->map->character_position_x)
            ->where('y', $character->map->character_position_y)
            ->where('is_port', true)
            ->exists();
    }
}
