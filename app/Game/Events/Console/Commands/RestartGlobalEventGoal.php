<?php

namespace App\Game\Events\Console\Commands;

use App\Flare\Models\GlobalEventGoal;
use App\Game\Events\Services\GlobalEventGoalProgressionService;
use Illuminate\Console\Command;

class RestartGlobalEventGoal extends Command
{
    protected $signature = 'restart:global-event-goal';

    protected $description = 'restarts the global event goal if it\'s been finished.';

    public function handle(GlobalEventGoalProgressionService $globalEventGoalProgressionService): void
    {
        GlobalEventGoal::whereNotNull('event_id')->get()->each(function (GlobalEventGoal $globalEventGoal) use ($globalEventGoalProgressionService) {
            $globalEventGoalProgressionService->advanceIfCurrentGoalComplete($globalEventGoal);
        });
    }
}
