<?php

namespace App\Console\DevelopmentCommands;

use App\Flare\Models\RankFight;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;

class IncreaseRankFightToMax extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'increase:rank-fight-to-max';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Increase rank fights to max.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void {
        $currentRankFight = RankFight::first();

        $ranksLeft = 50 - $currentRankFight->current_rank;

        if ($ranksLeft === 0) {
            $this->error('Already at Rank 50.');

            return;
        }

        $progressBar = new ProgressBar(new ConsoleOutput(),$ranksLeft);

        for ($i = 1; $i < $ranksLeft; $i++) {
            Artisan::call('update:rank-fights');

            $progressBar->advance();
        }

        $progressBar->finish();
    }
}
