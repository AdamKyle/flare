<?php

namespace App\Console\Commands;

use App\Flare\Models\UserLoginDuration;
use App\Game\Core\Events\WhosPlayingStatisticsUpdated;
use Carbon\Carbon;
use DB;
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
    public function handle() {

        $threshold = Carbon::now()->subMinutes(30); // example threshold, adjust as needed

        $updatedSessions = 0;

        UserLoginDuration::whereNull('logged_out_at')
            ->where('last_heart_beat', '<', $threshold) // Correct column name
            ->get()
            ->each(function ($login) use (&$updatedSessions) {
                $loggedInAt = Carbon::parse($login->logged_in_at);
                $lastHeartbeat = Carbon::parse($login->last_heart_beat); // Correct column name

                $login->logged_out_at = Carbon::now();
                $login->duration_in_seconds = $lastHeartbeat->diffInSeconds($loggedInAt);
                $login->save();

                $updatedSessions++;
            });

        if ($updatedSessions > 0) {
            broadcast(new WhosPlayingStatisticsUpdated());
        }
    }
}
