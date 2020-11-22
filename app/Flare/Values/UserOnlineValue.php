<?php

namespace App\Flare\Values;

use App\Flare\Models\Session;
use App\Flare\Models\User;

class UserOnlineValue {

    /**
     * Check if user is online.
     * 
     * online users have a user_id in the session table when they login, this allows us to say
     * that they are online.
     * 
     * @param User $user
     * @return bool
     */
    public function isOnline(User $user): bool {
        return !is_null(Session::where('user_id', $user->id)->first());
    }
}