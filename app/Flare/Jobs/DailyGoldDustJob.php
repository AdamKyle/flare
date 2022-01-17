<?php

namespace App\Flare\Jobs;

use Cache;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Database\Eloquent\Collection;
use App\Flare\Models\Character;
use App\Flare\Services\DailyGoldDustService;

class DailyGoldDustJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var Character $character
     */
    public Character $character;

    /**
     * Create a new job instance.
     *
     * @param Collection $characters
     */
    public function __construct(Character $character) {
        $this->character = $character;
    }

    public function handle(DailyGoldDustService $dailyGoldDustService) {

        if (Cache::has('won-lotto')) {
            if (Carbon::parse(Cache::get('won_lotto'))->isYesterday()) {
                Cache::delete('won-lotto');
            }
        }

        $dailyGoldDustService->handleDailyLottery($this->character);
    }
}
