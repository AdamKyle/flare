<?php

namespace App\Game\Events\Jobs;

use App\Flare\Models\Event;
use App\Flare\Models\ScheduledEvent;
use App\Game\Events\Values\EventType;
use App\Game\Messages\Events\GlobalMessageEvent;
use Facades\App\Game\Core\Handlers\AnnouncementHandler;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class InitiateWeeklyCelestialSpawnEvent implements ShouldQueue
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

        Cache::put('celestial-spawn-rate', .8);
        Cache::put('celestial-event-date', now()->addDay());

        Event::create([
            'type' => EventType::WEEKLY_CELESTIALS,
            'started_at' => $event->start_date,
            'ends_at' => $event->end_date,
        ]);

        event(new GlobalMessageEvent(
            'The gates have swung open and the Celestial\'s are free.
        get your weapons ready! (Celestials have a 80% chance to spawn regardless of plane based on any
        movement type except traverse - for the next 24 hours! Only one will spawn per hour unless it is killed.)'
        ));

        AnnouncementHandler::createAnnouncement('weekly_celestial_spawn');
    }
}
