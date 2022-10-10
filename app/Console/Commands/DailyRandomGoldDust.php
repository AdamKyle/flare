<?php

namespace App\Console\Commands;

use App\Flare\Jobs\DailyGoldDustJob;
use App\Flare\Models\Character;
use App\Flare\Services\DailyGoldDustService;
use Illuminate\Console\Command;
use Facades\App\Flare\Values\UserOnlineValue;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;

class DailyRandomGoldDust extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'daily:gold-dust';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gives random amount of gold dust to all characters per day, with chance to win lottery.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(DailyGoldDustService $dailyGoldDustService) {
        $characterIds = Character::pluck('id')->toArray();

        $maxCharacters = count($characterIds) - 1;

        $randomIndex = rand(0, $maxCharacters);

        $characterWhoWon = $characterIds[$randomIndex];

        $character = Character::find($characterWhoWon);

        $dailyGoldDustService->handleWonDailyLottery($character);

        Cache::delete('daily-gold-dust-lottery-won');

        $progressBar = new ProgressBar(new ConsoleOutput(), Character::count());

        Character::chunkById(100, function($characters) use ($characterWhoWon, $dailyGoldDustService, $progressBar) {
            foreach ($characters as $character) {
                if ($character->id !== $characterWhoWon) {
                    $dailyGoldDustService->handleRegularDailyGoldDust($character);

                    $progressBar->advance();
                }
            }
        });

        $progressBar->finish();
    }
}
