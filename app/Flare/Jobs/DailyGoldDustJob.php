<?php

namespace App\Flare\Jobs;

use App\Flare\Events\ServerMessageEvent;
use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Models\Character;
use App\Game\Messages\Events\GlobalMessageEvent;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class DailyGoldDustJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var Character $character
     */
    public Character $character;

    /**
     * The chance is one-in-a-million.
     *
     * @var int $lottoChance
     */
    private $lottoChance = 999999;

    /**
     * The max for the lottery
     *
     * @var int $lottoMax
     */
    private $lottoMax     = 10000;

    /**
     * Create a new job instance.
     *
     * @param Collection $characters
     */
    public function __construct(Character $character) {
        $this->character = $character;
    }

    public function handle() {

        if (Cache::has('won-lotto')) {
            if (Carbon::parse(Cache::get('won_lotto'))->isYesterday()) {
                Cache::delete('won-lotto');
            }
        }

        $lottoChance = rand(1, 1000000);

        if ($lottoChance > $this->lottoChance && !Cache::has('won-lotto')) {
            event(new GlobalMessageEvent($this->character->name . 'has won the daily Gold Dust Lottery!'));

            $this->character->update([
                'gold_dust' => $this->character->gold_dust + $this->lottoMax,
            ]);

            event(new ServerMessageEvent($this->character->user, 'lotto_max', $this->lottoMax));

            event(new UpdateTopBarEvent($this->character->refresh()));

            Cache::put('won-lotto', now());
        } else {
            $amount = rand(1,1000);

            $this->character->update([
                'gold_dust' => $this->character->gold_dust + $amount,
            ]);

            event(new ServerMessageEvent($this->character->user, 'daily_lottery', $amount));

            event(new UpdateTopBarEvent($this->character->refresh()));
        }
    }
}
