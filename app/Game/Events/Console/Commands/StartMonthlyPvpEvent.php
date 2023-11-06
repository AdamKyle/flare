<?php

namespace App\Game\Events\Console\Commands;

use App\Flare\Models\Announcement;
use App\Flare\Models\Character;
use App\Flare\Models\Event;
use App\Game\Events\Values\EventType;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Battle\Jobs\MonthlyPvpAutomation;
use App\Game\Messages\Events\DeleteAnnouncementEvent;
use App\Game\Messages\Events\GlobalMessageEvent;
use Illuminate\Console\Command;

class StartMonthlyPvpEvent extends Command {
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

        event(new GlobalMessageEvent('Those participating in Monthly PVP will be moved to the Arena (on Surface) in 15 minutes.
        All current explorations for these players will stop. You will be considered in "automation" for the time you are in the
        Arena. Finally after being moved and before the fight, your screen will refresh
        automatically to close any open dialogues and so on. You have 15 minutes to prepare for PVP (before the move and refresh). All PVP attacks have been locked!'));

        MonthlyPvpAutomation::dispatch()->delay(now()->addMinutes(1));

        Character::chunkById(100, function ($character) {
            foreach ($character as $character) {
                event(new UpdateCharacterStatus($character));
            }
        });

        $event = Event::where('type', EventType::MONTHLY_PVP)->first();

        $announcement = Announcement::where('event_id', $event->id)->first();

        event(new DeleteAnnouncementEvent($announcement->id));

        $announcement->delete();

        $event->delete();
    }
}
