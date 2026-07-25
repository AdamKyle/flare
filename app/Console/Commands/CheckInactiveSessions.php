<?php

namespace App\Console\Commands;

use App\Flare\Models\UserLoginDuration;
use App\Game\Core\Events\WhosPlayingStatisticsUpdated;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckInactiveSessions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:inactive-sessions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks for and updates - inactive sessions';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $threshold = Carbon::now()->subMinutes(30); // example threshold, adjust as needed
        $updatedSessions = 0;
        $now = now();

        UserLoginDuration::whereNull('logged_out_at')
            ->whereNull('duration_in_seconds')
            ->get()
            ->each(function (UserLoginDuration $login) use (&$updatedSessions, $now, $threshold): void {
                $loggedOutAt = collect([
                    $login->last_heart_beat,
                    $login->last_activity,
                ])->filter()->sortByDesc(fn ($activity) => $activity->getTimestamp())->first() ?? $login->logged_in_at;

                if ($loggedOutAt->lt($login->logged_in_at)) {
                    $loggedOutAt = $login->logged_in_at;
                }

                if ($loggedOutAt->gt($now)) {
                    $loggedOutAt = $now;
                }

                if ($loggedOutAt->gte($threshold)) {
                    return;
                }

                $login->update([
                    'logged_out_at' => $loggedOutAt,
                    'duration_in_seconds' => $login->logged_in_at->diffInSeconds($loggedOutAt),
                ]);

                $updatedSessions++;
            });

        if ($updatedSessions > 0) {
            broadcast(new WhosPlayingStatisticsUpdated());
        }
    }
}
