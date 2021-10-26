<?php

namespace App\Console\Commands;

use App\Flare\Jobs\DailyGoldDustJob;
use App\Flare\Models\Character;
use Illuminate\Console\Command;
use Facades\App\Flare\Values\UserOnlineValue;

class DailyRandomGoldDust extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'daily:gold-dust';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gives random amount of gold dust to all characters per day, with chance to win lottery.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle() {
        Character::chunkById(100, function($characters) {
            foreach ($characters as $character) {
                DailyGoldDustJob::dispatch($character)->onConnection('character_daily');
            }
        });
    }
}
