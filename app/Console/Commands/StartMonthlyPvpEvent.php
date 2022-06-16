<?php

namespace App\Console\Commands;

use App\Flare\Models\Event;
use App\Flare\Values\EventType;
use App\Game\Battle\Jobs\MonthlyPvpAutomation;
use App\Game\Messages\Events\GlobalMessageEvent;
use Illuminate\Console\Command;

class StartMonthlyPvpEvent extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'start:pvp-monthly-event';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Starts the pvp event';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle() {
        Event::where('type', EventType::MONTHLY_PVP)->delete();

        event(new GlobalMessageEvent('Those participating in Monthly PVP will be moved to the Arena (on Surface) in 15 minutes.
        All current explorations for these players will stop. You will be considered in "automation" for the time you are in the
        Arena. Finally after being moved and before the fight, your screen will refresh
        automatically to close any open dialogues and so on. You have 15 minutes to prepare for PVP. All PVP attacks have been locked!'));

        MonthlyPvpAutomation::dispatch()->delay(now()->addMinutes(15));
    }
}
