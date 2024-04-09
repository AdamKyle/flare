<?php

namespace App\Console\Commands;

use App\Flare\Models\WeeklyMonsterFight;
use Illuminate\Console\Command;

class ResetWeeklyFights extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reset:weekly-fights';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Resets Weekly Fights';

    /**
     * Execute the console command.
     */
    public function handle() {
        WeeklyMonsterFight::truncate();
    }
}
