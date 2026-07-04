<?php

namespace App\Game\Events\Console\Commands;

use App\Flare\Models\GlobalEventGoal;
use App\Game\Events\Services\GlobalEventGoalProgressionService;
use Illuminate\Console\Command;

class RestartGlobalEventGoal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'restart:global-event-goal';

    /**
     * The console command description.Set the battle event.
     *
     * @var string
     */
    protected $description = 'restarts the global event goal if it\'s been finished.';

    /**
     * Handle restarting the global event.
     */
    public function handle(GlobalEventGoalProgressionService $globalEventGoalProgressionService): void
    {
        $globalEvent = GlobalEventGoal::first();

        if (is_null($globalEvent)) {
            return;
        }

        $globalEventGoalProgressionService->advanceIfCurrentGoalComplete($globalEvent);
    }
}
