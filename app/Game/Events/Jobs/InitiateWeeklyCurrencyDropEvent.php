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

        WeeklyCurrencyEventJob::dispatch()->delay(now()->addMinutes(15))->onConnection('weekly_spawn');

        event(new GlobalMessageEvent('Currencies are dropping like crazy! Shards, Copper Coins and
        Gold Dust are falling off the enemies for one day only! At a rate of 1-50 per currency type.'));
    }
}
