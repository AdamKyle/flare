<?php

namespace App\Flare\Jobs;

use App\Flare\Models\Event;
use App\Game\Events\Values\EventType;
use App\Game\Messages\Events\GlobalMessageEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class WeeklyCurrencyEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct() {}

    public function handle()
    {

        $event = Event::where('type', EventType::WEEKLY_CURRENCY_DROPS)->first();

        if (is_null($event)) {
            return;
        }

        if (now()->isAfter($event->ends_at)) {

            event(new GlobalMessageEvent('Currency drops have returned to normal! Come back next sunday!'));

            $event->delete();
        } else {
            WeeklyCurrencyEvent::dispatch()->delay(now()->addMinutes(15))->onConnection('weekly_spawn');
        }
    }
}
