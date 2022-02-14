<?php

namespace App\Game\Exploration\Console\Commands;

use App\Flare\Models\Character;
use App\Game\Messages\Events\GlobalMessageEvent;
use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Flare\Models\AdventureLog;

class ClearExplorationTimeOuts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clear:locked-exploration';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clears players who\'s exploration is locked.';

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
     *
     * @return mixed
     */
    public function handle()
    {
        Character::where('is_attack_automation_locked', true)->update([
            'is_attack_automation_locked' => false,
        ]);

        event(new GlobalMessageEvent('Exploration has been unlocked for all characters who had it previously locked due exploring for 8 solid hours. Refresh if you were effected.'));

    }
}
