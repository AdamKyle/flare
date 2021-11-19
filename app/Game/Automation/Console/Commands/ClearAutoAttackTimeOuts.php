<?php

namespace App\Game\Automation\Console\Commands;

use App\Flare\Models\Character;
use App\Game\Messages\Events\GlobalMessageEvent;
use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Flare\Models\AdventureLog;

class ClearAutoAttackTimeOuts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clear:locked-auto-attack';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clears players who\'s auto attack is locked.';

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

        event(new GlobalMessageEvent('Auto attack locks have been removed. Refresh if yours is locked.'));
    }
}
