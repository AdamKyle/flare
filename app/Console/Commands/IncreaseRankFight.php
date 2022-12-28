<?php

namespace App\Console\Commands;

use App\Flare\Models\RankFight;
use App\Flare\Models\RankFightTop;
use App\Game\Messages\Events\GlobalMessageEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class IncreaseRankFight extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:rank-fights';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Increase the rank fights and reset the tops list';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void {
        RankFightTop::truncate();

        $currentRank = RankFight::first();

        if (is_null($currentRank)) {
            RankFight::create([
                'current_rank' => 10,
            ]);

            Artisan::call('generate:monster-cache');

            return;
        }

        if ($currentRank->current_rank < 50) {
            $currentRank->update([
                'current_rank' => $currentRank->current_rank + 1,
            ]);

            $currentRank = $currentRank->refresh();

            Artisan::call('generate:monster-cache');

            event(new GlobalMessageEvent('Rank fights have a new rank: Rank ' . $currentRank->current_rank . '. head to Underwater Caves to test your might against these fearsome beasts!'));
        }
    }
}
