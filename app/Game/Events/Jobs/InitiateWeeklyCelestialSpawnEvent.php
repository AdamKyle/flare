<?php

namespace App\Game\Events\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Flare\Jobs\WeeklyCelestialSpawnEvent as SpawnCancelingJob;
use App\Flare\Models\Event;
use App\Flare\Models\ScheduledEvent;
use App\Flare\Values\EventType;
use App\Game\Messages\Events\GlobalMessageEvent;
use Facades\App\Game\Core\Handlers\AnnouncementHandler;
use Illuminate\Support\Facades\Cache;

class InitiateWeeklyCelestialSpawnEvent implements ShouldQueue {

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
        $this->eventId   = $eventId;
    }

    /**
     * @return void
     */
    public function handle(): void {

        $event = ScheduledEvent::find($this->eventId);

        if (is_null($event)) {

            return;
        }

        Cache::put('celestial-spawn-rate', .8);
        Cache::put('celestial-event-date', now()->addDay());

        Event::create([
            'type'       => EventType::WEEKLY_CELESTIALS,
            'started_at' => now(),
            'ends_at'    => now()->addDay()
        ]);

        SpawnCancelingJob::dispatch()->delay(now()->addMinutes(15))->onConnection('weekly_spawn');

        event(new GlobalMessageEvent(
            'The gates have swung open and the Celestial\'s are free.
        get your weapons ready! (Celestials have a 80% chance to spawn regardless of plane based on any
        movement type except traverse - for the next 24 hours! Only one will spawn per hour unless it is killed.)'
        ));

        AnnouncementHandler::createAnnouncement('weekly_celestial_spawn');
    }
}
