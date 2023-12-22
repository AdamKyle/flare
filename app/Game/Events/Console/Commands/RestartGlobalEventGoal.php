<?php

namespace App\Game\Events\Console\Commands;

use App\Flare\Models\Announcement;
use App\Flare\Models\Character;
use App\Flare\Models\Event;
use App\Flare\Models\GlobalEventGoal;
use App\Game\Events\Values\EventType;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Battle\Jobs\MonthlyPvpAutomation;
use App\Game\Messages\Events\DeleteAnnouncementEvent;
use App\Game\Messages\Events\GlobalMessageEvent;
use Illuminate\Console\Command;

class RestartGlobalEventGoal extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'restart:global-event-goal';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'restarts the global event goal if it\'s been finished.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void {

        $globalEvent = GlobalEventGoal::first();

        if ($globalEvent->total_kills < $globalEvent->max_kills) {
            return;
        }

        $globalEvent->globalEventParticipation()->truncate();
        $globalEvent->globalEventKills()->truncate();

        event(new GlobalMessageEvent(
            'Global Event Goal for: ' . $globalEvent->eventType()->getNameForEvent(). ' Players can now participate again and earn
            Rewards for meeting the various phases! How exciting!'
        ));
    }
}
