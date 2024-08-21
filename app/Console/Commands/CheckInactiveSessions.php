<?php

namespace App\Console\Commands;

use App\Flare\Models\UserLoginDuration;
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

        $threshold = now()->subHour();

        UserLoginDuration::whereNull('logged_out_at')
            ->where('last_heartbeat_at', '<', $threshold)
            ->update([
                'logged_out_at' => now(),
                'duration_in_seconds' => DB::raw('TIMESTAMPDIFF(SECOND, logged_in_at, last_heartbeat_at)'),
            ]);
    }
}
