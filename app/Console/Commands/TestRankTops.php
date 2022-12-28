<?php

namespace App\Console\Commands;

use App\Flare\Models\Character;
use App\Flare\Models\RankFight;
use Carbon\Carbon;
use Illuminate\Console\Command;

class TestRankTops extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:rank-tops';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void {

        Character::chunkById(100, function($characters) {
           foreach ($characters as $character) {
               $character->ranktop()->create([
                   'character_id'          => $character->id,
                   'current_rank'          => rand(1, RankFight::first()->current_rank),
                   'rank_achievement_date' => $this->getRankAchievementDate(),
               ]);
           }
        });
    }

    protected function getRankAchievementDate(): Carbon {
        $number = rand(1, 100);

        if ($number > 50) {
            return (new Carbon())->addDays(rand(1, 10));
        }

        return (new Carbon())->subDays(rand(1, 10));
    }
}
