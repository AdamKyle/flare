<?php

namespace App\Flare\Listeners;

use App\Flare\Events\UpdateSiteStatisticsChart;
use App\Flare\Models\User;
use App\Flare\Models\UserLoginDuration;
use App\Flare\Models\UserSiteAccessStatistics;
use Carbon\Carbon;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Registered;
use Illuminate\Broadcasting\PendingBroadcast;

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
