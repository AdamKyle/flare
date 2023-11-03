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
use App\Flare\Values\EventType;
use Facades\App\Game\Core\Handlers\AnnouncementHandler;

class InitiateWinterEvent implements ShouldQueue {

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
            'type'       => EventType::WINTER_EVENT,
            'started_at' => now(),
            'ends_at'    => $event->end_date
        ]);

        event(new GlobalMessageEvent('The Ice Queen has opened the gates to her realm. She calls on valiant heros of Tlessa to help her fight back the corruption that seeks to melt her dynasty!'));

        AnnouncementHandler::createAnnouncement('winter_event');
    }
}
