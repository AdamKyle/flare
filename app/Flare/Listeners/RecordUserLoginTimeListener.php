<?php

namespace App\Flare\Listeners;

use App\Flare\Models\UserLoginDuration;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Registered;

class RecordUserLoginTimeListener
{
    /**
     * Handle the event.
     */
    public function handle(Login|Registered $event)
    {

        $user = $event->user;

        if ($user->hasRole('Admin')) {
            return;
        }

        UserLoginDuration::create([
            'user_id' => $user->id,
            'logged_in_at' => now(),
            'last_heart_beat' => now(),
            'last_activity' => now(),
        ]);
    }
}
