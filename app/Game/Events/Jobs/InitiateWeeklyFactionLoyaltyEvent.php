<?php

namespace App\Game\Events\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Flare\Models\ScheduledEvent;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Flare\Models\Event;
use App\Game\Events\Values\EventType;
use Facades\App\Game\Core\Handlers\AnnouncementHandler;

class InitiateWeeklyFactionLoyaltyEvent implements ShouldQueue {

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var int $eventId
     */
    protected int $eventId;

    /**
     * Create a new job instance.
     *
     * @param int $eventId
     */
    public function __construct(int $eventId) {
        $this->eventId = $eventId;
    }

    /**
     * @return void
     */
    public function handle(): void {

        $event = ScheduledEvent::find($this->eventId);

        if (is_null($event)) {

            return;
        }

        $event->update([
            'currently_running' => true,
        ]);

        Event::create([
            'type'       => EventType::WEEKLY_FACTION_LOYALTY_EVENT,
            'started_at' => now(),
            'ends_at'    => now()->addDay()
        ]);

        event(new GlobalMessageEvent('Weekly Faction Loyalty Event has started. Players, for the next 24 hours, can gain 2 points in any task
        they ar doing for the NPC. When NPC\'s tasks refresh, they will refresh with half the required amount for each task.'));

        AnnouncementHandler::createAnnouncement('weekly_faction_loyalty_event');
    }
}
