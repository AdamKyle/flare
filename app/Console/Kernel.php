<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\CleanMarketHistory;
use App\Console\Commands\CleanNotifications;
use App\Console\Commands\MoveInfoFiles;
use App\Console\Commands\UpdateKingdom;
use Spatie\ShortSchedule\ShortSchedule;

/**
 * @codeCoverageIgnore
 */
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

        // Increase the max level every month.
        $schedule->command('increase:max_level')->monthly()->timezone(config('app.timezone'));

        // Delete the flagged users once a month.
        $schedule->command('delete:flagged-users')->monthly()->timezone(config('app.timezone'));

        // Update kingdoms every hour.
        $schedule->command('update:kingdom')->hourly()->timezone(config('app.timezone'));

        // Refresh the droppable items.
        $schedule->command('cache:droppable-items')->everySixHours()->timezone(config('app.timezone'));

        // Refresh the high-end droppable items.
        $schedule->command('cache:highend-droppable-items')->everyThreeHours()->timezone(config('app.timezone'));

        // Give people a chance to win daily lottery for gold dust
        $schedule->command('daily:gold-dust')->dailyAt('12:00')->timezone(config('app.timezone'));

        // Weekly Celestial Rate is increased to 80% spawn chance on Wednesdays at 1 pm America Edmonton time.
        $schedule->command('weekly:celestial-spawn')->weeklyOn(3, '13:00')->timezone(config('app.timezone'));

        // Clear the celestials every hour.
        $schedule->command('clear:celestials')->hourly()->timezone(config('app.timezone'));

        // Clean up enchanted items every week at 2am:
        $schedule->command('clean:enchanted-items')->weeklyOn(7, '2:00')->timezone(config('app.timezone'));

        // Clean the market every three months starting at 2am.
        $schedule->command('clean:market-history')->cron('0 2 * */3 *')->timezone(config('app.timezone'));

        // Flag inactive users every 5 months at 2am.
        $schedule->command('flag:users-for-deletion')->cron('0 2 * */5 *')->timezone(config('app.timezone'));

        // Clean the chat every three months starting at 2am.
        $schedule->command('clean:chat')->cron('0 2 * */3 *')->timezone(config('app.timezone'));

        // clean the kingdom logs every week on monday at 2 am.
        $schedule->command('clean:kingdomLogs')->weeklyOn(1, '2:00')->timezone(config('app.timezone'));

        // clean the adventure logs every week on monday at 2 am.
        $schedule->command('clean:adventure-logs')->weeklyOn(1, '2:00')->timezone(config('app.timezone'));
    }

    /**
     * Spatties short scheduler
     *
     * This allows commands to run very fast, as opposed to every minute at the least.
     *
     * @param ShortSchedule $schedule
     */
    protected function shortSchedule(ShortSchedule $schedule) {
        $schedule->command('update:map-count')->everySeconds(5);
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
