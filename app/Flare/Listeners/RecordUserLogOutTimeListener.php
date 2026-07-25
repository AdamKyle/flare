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

        $foundRecord = UserLoginDuration::where('user_id', $user->id)
            ->whereNull('logged_out_at')
            ->whereNull('duration_in_seconds')
            ->latest('logged_in_at')
            ->first();

        if (is_null($foundRecord)) {
            return;
        }

        $loggedOutAt = now();

        if ($loggedOutAt->lt($foundRecord->logged_in_at)) {
            $loggedOutAt = $foundRecord->logged_in_at;
        }

        $foundRecord->update([
            'logged_out_at' => $loggedOutAt,
            'duration_in_seconds' => $foundRecord->logged_in_at->diffInSeconds($loggedOutAt),
            'last_heart_beat' => now(),
            'last_activity' => now(),
        ]);

        broadcast(new AdminStatisticsDashboardUpdated());
        broadcast(new WhosPlayingStatisticsUpdated());
    }
}
