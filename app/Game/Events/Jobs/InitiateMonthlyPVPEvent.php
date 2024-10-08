<?php

namespace App\Game\Events\Jobs;

use App\Flare\Models\Event;
use App\Flare\Models\ScheduledEvent;
use App\Flare\Models\User;
use App\Flare\Values\UserOnlineValue;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Events\Values\EventType;
use App\Game\Messages\Events\GlobalMessageEvent;
use Facades\App\Game\Core\Handlers\AnnouncementHandler;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class InitiateMonthlyPVPEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $eventId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $eventId)
    {
        $this->eventId = $eventId;
    }

    public function handle(): void
    {

        $event = ScheduledEvent::find($this->eventId);

        if (is_null($event)) {

            return;
        }

        $event->update([
            'currently_running' => true,
        ]);

        $event = $event->refresh();

        Event::create([
            'type' => EventType::MONTHLY_PVP,
            'started_at' => $event->start_date,
            'ends_at' => $event->end_date,
        ]);

        event(new GlobalMessageEvent('Monthly pvp will begin tonight shortly after 6:30 GMT-6. Actions area has been updated to show a new button: Join PVP. Click this and follow the steps to be registered to participate. Registration will be open till 6:30pm GMT-6.'));

        (new UserOnlineValue)->getUsersOnlineQuery()->chunkById(100, function ($sessions) {
            foreach ($sessions as $session) {
                $user = User::find($session->user_id);

                if (! is_null($user->character)) {
                    if ($user->character->level >= 302) {
                        event(new UpdateCharacterStatus($user->character));
                    }
                }
            }
        });

        AnnouncementHandler::createAnnouncement('monthly_pvp');
    }
}
