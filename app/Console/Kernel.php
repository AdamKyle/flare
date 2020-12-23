<?php

namespace App\Console;

use App\Console\Commands\CleanMarketHistory;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\CleanNotifications;
use App\Console\Commands\CreateFakeUsers;
use App\Console\Commands\GiveCharacterGold;
use App\Console\Commands\GiveItem;
use App\Console\Commands\GenerateLoations;
use App\Console\Commands\LevelFakeUsers;
use App\Console\Commands\LevelUpSkillsOnFakeUsers;
use App\Console\Commands\MoveInfoFiles;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        GiveItem::class,
        GiveCharacterGold::class,
        CleanNotifications::class,
        MoveInfoFiles::class,
        CreateFakeUsers::class,
        LevelUpSkillsOnFakeUsers::class,
        LevelFakeUsers::class,
        CleanMarketHistory::class,
        GenerateLoations::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {

        $schedule->command('clean:notifications')->monthly();
        
        $schedule->command('clean:market-history')->cron('0 0 1 */3 *');
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
