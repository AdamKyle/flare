<?php

namespace App\Console\Commands;

use App\Flare\Models\Character;
use App\Flare\Models\Event;
use App\Flare\Models\User;
use App\Flare\Values\EventType;
use App\Flare\Values\UserOnlineValue;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Messages\Events\GlobalMessageEvent;
use Illuminate\Console\Command;

class MonthlyPvPEvent extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monthly:pvp';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Allows player to register for the pvp event.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle() {
        event(new GlobalMessageEvent('Monthly pvp will begin tonight at 7pm GMT-6. Actions area has been updated to show a new button: Join PVP. Click this and follow the steps to be registered to participate. Registration will be open till 6:30pm GMT-6 (10 Hours, 30 Minutes from now)'));

        Event::create([
            'type'       => EventType::MONTHLY_PVP,
            'started_at' => now(),
            'ends_at'    => now()->addHours(10)->addMinutes(30)
        ]);

        (new UserOnlineValue())->getUsersOnlineQuery()->chunkById(100, function($sessions) {
            foreach ($sessions as $session) {
                $user = User::find($session->user_id);

                if (!is_null($user->character)) {
                    event(new UpdateCharacterStatus($user->character));
                }
            }
        });
    }
}
