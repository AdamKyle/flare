<?php

namespace App\Console\Commands;

use App\Game\Messages\Events\GlobalMessageEvent;
use Illuminate\Console\Command;
use App\Flare\Models\Event;
use App\Flare\Values\EventType;
use App\Flare\Jobs\WeeklyCurrencyEvent as WeeklyCurrencyEventJob;

class WeeklyCurrencyDropEvent extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'weekly:currency-drop-event';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Allows players to get all currencies in a fight.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void {
        Event::create([
            'type'       => EventType::WEEKLY_CURRENCY_DROPS,
            'started_at' => now(),
            'ends_at'    => now()->addDay()
        ]);

        WeeklyCurrencyEventJob::dispatch()->delay(now()->addMinutes(15))->onConnection('weekly_spawn');

        event(new GlobalMessageEvent('Currencies are dropping like crazy! Shards, Copper Coins and
        Gold Dust are falling off the enemies for one day only! At a rate of 1-25 per currency type.'));
    }
}
