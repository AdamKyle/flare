<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Spatie\ShortSchedule\ShortSchedule;

/**
 * @codeCoverageIgnore
 */
class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {

        // Delete the flagged users once a month.
        $schedule->command('delete:flagged-users')->monthly()->timezone(config('app.timezone'));

        // Clear the celestials every hour.
        $schedule->command('clear:celestials')->hourly()->timezone(config('app.timezone'));

        // Clean the market every three months starting at 2am.
        $schedule->command('clean:market-history')->cron('0 2 * */3 *')->timezone(config('app.timezone'));

        // Flag inactive users every 5 months at 2am.
        $schedule->command('flag:users-for-deletion')->cron('0 2 * */5 *')->timezone(config('app.timezone'));

        // Clean the chat every three months starting at 2am.
        $schedule->command('clean:chat')->cron('0 2 * */3 *')->timezone(config('app.timezone'));

        // Clean Weekly Fights every Sunday at 3am
        $schedule->command('reset:weekly-fights')->cron('0 3 * * 0')->timezone(config('app.timezone'));

        // clean the kingdom logs every week on monday at 2 am.
        $schedule->command('clean:kingdomLogs')->weeklyOn(1, '2:00')->timezone(config('app.timezone'));

        // Unlock market listings that have been locked for a day.
        $schedule->command('unlock:market-listings')->dailyAt('02:20')->timezone(config('app.timezone'));

        // Clean up the items every day.
        $schedule->command('cleanup:unused-items')->dailyAt('03:00')->timezone(config('app.timezeon'));

        // Generate new scheduled events based on the Scheduled event configuration
        $schedule->command('generate:scheduled-events')->dailyAt('04:00')->timezone(config('app.timezeon'));

        // Fix Character Timers every minute.
        $schedule->command('reset:timers')->everyMinute();

        // Determine if we should alert on new guide quest.
        $schedule->command('check:for-complete-guide-quests')->everySecond();

        // See if we need to start a survey for a player while the feddback event is running.
        $schedule->command('start:survey')->everyMinute();

        // See if we have inactive sessions that need to be filled out.
        $schedule->command('check:inactive-sessions')->everyFifteenMinutes();

        /**
         * Game Events:
         */

        // Update kingdoms every hour.
        $schedule->command('update:kingdoms')->hourly()->timezone(config('app.timezone'));

        // Reset every day at 11:59 pm.
        $schedule->command('reset:capital-city-walking-status')->dailyAt('23:59')->timezone(config('app.timezone'));

        // Give people a chance to win daily lottery for gold dust
        $schedule->command('daily:gold-dust')->dailyAt('12:00')->timezone(config('app.timezone'));

        // Reset raid attacks every day.
        $schedule->command('reset:daily-raid-attack-limits')->dailyAt('04:00')->timezone(config('app.timezone'));

        // process and start any scheduled events.
        $schedule->command('process:scheduled-events')->everyFiveMinutes()->timezone(config('app.timezone'));

        // Process ressurecting a raid boss.
        $schedule->command('ressurect:raid-boss')->hourly()->timezone(config('app.timezone'));

        // End scheduled events
        $schedule->command('end:scheduled-event')->everyFiveMinutes()->timezone(config('app.timezone'));

        // Restart the global events.
        $schedule->command('restart:global-event-goal')->hourly()->timezone(config('app.timezone'));
    }

    /**
     * Spatties short scheduler
     *
     * This allows commands to run very fast, as opposed to every minute at the least.
     */
    protected function shortSchedule(ShortSchedule $schedule)
    {
        $schedule->command('update:map-count')->everySeconds(5);
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
