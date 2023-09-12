<?php

namespace App\Game\Raids\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Flare\Models\ScheduledEvent;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Flare\Jobs\WeeklyCurrencyEvent as WeeklyCurrencyEventJob;
use App\Flare\Models\Event;
use App\Flare\Values\EventType;
use Facades\App\Game\Core\Handlers\AnnouncementHandler;

class InitiateWeeklyCurrencyDropEvent implements ShouldQueue {

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

        Event::create([
            'type'       => EventType::WEEKLY_CURRENCY_DROPS,
            'started_at' => now(),
            'ends_at'    => now()->addDay()
        ]);

        WeeklyCurrencyEventJob::dispatch()->delay(now()->addMinutes(15))->onConnection('weekly_spawn');

        event(new GlobalMessageEvent('Currencies are dropping like crazy! Shards, Copper Coins (for those with the quest item) and
        Gold Dust are falling off the enemies for one day only! At a rate of 1-50 per currency type.'));

        AnnouncementHandler::createAnnouncement('weekly_currency_drop');
    }
}
