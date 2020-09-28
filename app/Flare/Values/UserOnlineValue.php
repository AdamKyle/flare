<?php

namespace App\Flare\Values;

use App\Flare\Models\Session;
use App\User;

class UserOnlineValue {

    public function isOnline(User $user) {
        return !is_null(Session::where('user_id', $user->id)->first());
    }
}