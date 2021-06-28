<?php

namespace App\Console;

use App\Console\Commands\CleanMarketHistory;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\CleanNotifications;
use App\Console\Commands\MoveInfoFiles;
use App\Console\Commands\UpdateKingdom;

class Kernel extends ConsoleKernel {

    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        CleanNotifications::class,
        MoveInfoFiles::class,
        CleanMarketHistory::class,
        UpdateKingdom::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule) {

        // Clean notifications every month.
        $schedule->command('clean:notifications')->monthly()->timezone(config('app.timezone'));

        // Update kingdoms every hour.
        $schedule->command('update:kingdom')->hourly()->timezone(config('app.timezone'));

        // Clear the celestials every hour.
        $schedule->command('clear:celestials')->hourly()->timezone(config('app.timezone'));

        // Clean the market every three months starting at 2am.
        $schedule->command('clean:market-history')->cron('0 2 * */3 *')->timezone(config('app.timezone'));

        // Clean the chat every three months starting at 2am.
        $schedule->command('clean:chat')->cron('0 2 * */3 *')->timezone(config('app.timezone'));

        // clean the kingdom logs every week on monday at 2 am.
        $schedule->command('clean:kingdomLogs')->weeklyOn(1, '2:00')->timezone(config('app.timezone'));

        // clean the adventure logs every week on monday at 2 am.
        $schedule->command('clean:adventure-logs')->weeklyOn(1, '2:00')->timezone(config('app.timezone'));
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
