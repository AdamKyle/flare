<?php

namespace App\Flare\Listeners;

use App\Admin\Events\AdminStatisticsDashboardUpdated;
use App\Flare\Models\UserLoginDuration;
use App\Game\Core\Events\WhosPlayingStatisticsUpdated;
use Illuminate\Auth\Events\Logout;

class RecordUserLogOutTimeListener
{
    /**
     * Handle the event.
     */
    public function handle(Logout $event)
    {
        if (is_null($event->user)) {
            return;
        }

        if ($event->user->hasRole('Admin')) {
            return;
        }

        $user = $event->user;

        $foundRecord = UserLoginDuration::where('user_id', $user->id)->latest()->first();

        if (is_null($foundRecord)) {
            return;
        }

        $now = now();

        $foundRecord->update([
            'logged_out_at' => $now,
            'duration_in_seconds' => $now->diffInSeconds($foundRecord->logged_in_at),
            'last_heart_beat' => now(),
            'last_activity' => now(),
        ]);

        broadcast(new AdminStatisticsDashboardUpdated());
        broadcast(new WhosPlayingStatisticsUpdated());
    }
}
