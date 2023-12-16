<?php

namespace App\Console\AfterDeployment;

use App\Flare\Models\GlobalEventGoal;
use App\Game\Events\Values\EventType;
use App\Game\Events\Values\GlobalEventForEventTypeValue;
use Illuminate\Console\Command;

class KickOffEventGoalForWinterEvent extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kick:off-event-goal-for-winter-event';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'kick off event goals for winter event.';

    /**
     * Execute the console command.
     */
    public function handle() {
        $globalEventGoalData = GlobalEventForEventTypeValue::returnGlobalEventInfoForSeasonalEvents(EventType::WINTER_EVENT);

        GlobalEventGoal::create($globalEventGoalData);
    }
}
