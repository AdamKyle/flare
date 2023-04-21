<?php

namespace App\Flare\Values;

use App\Flare\Models\Session;
use App\Flare\Models\User;
use App\Game\Kingdoms\Events\UpdateGlobalMap;
use App\Game\Maps\Events\UpdateMapDetailsBroadcast;
use Illuminate\Database\Eloquent\Collection;

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

    /**
     * Returns a collection of users currently online.
     *
     * @return Collection
     * @codeCoverageIgnore
     */
    public function getUsersOnline(): Collection {
        return Session::where('last_activity', '<', now()->addHour()->timestamp)
                      ->whereNotNull('user_id')
                      ->join('users', function($join) {
                          $join->on('users.id', 'sessions.user_id');
                      })->select('users.*')->get();
    }

    /**
     * Returns a query object of sessions currently active.
     *
     * @return mixed
     * @codeCoverageIgnore
     */
    public function getUsersOnlineQuery() {
        return Session::where('last_activity', '<', now()->addHour()->timestamp)
                      ->whereNotNull('user_id');

    }
}
