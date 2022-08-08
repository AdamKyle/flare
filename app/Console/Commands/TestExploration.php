<?php

namespace App\Console\Commands;

use App\Flare\Models\GameMap;
use App\Game\Exploration\Services\ExplorationAutomationService;
use App\Game\Maps\Services\TraverseService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;
use App\Flare\Models\Character;

class TestExploration extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:exploration {amount}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Exploration';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(ExplorationAutomationService $explorationAutomationService, TraverseService $traverseService) {

        $amount          = $this->argument('amount');
        $totalCharacters = DB::table('characters')->count();

        if ($amount > $totalCharacters) {
            $this->line('There is only: ' . $totalCharacters . ' in game. Setting that as max.');

            $amount = $totalCharacters;
        }

        $characters = Character::where('name', '!=', 'Credence')->take($amount)->get();

        $progressBar = new ProgressBar(new ConsoleOutput(), $characters->count());

        $skipped = null;

        foreach ($characters as $character) {
            if ($character->name === 'Credence') {
                $skipped = $character->name;

                $progressBar->advance();

                continue;
            }

            if (!$character->map->gameMap->mapType()->isSurface()) {
                $traverseService->travel(GameMap::where('name', 'Surface')->first()->id, $character);
            }

            $explorationAutomationService->beginAutomation($character->refresh(), [
                'auto_attack_length'       => 1,
                'selected_monster_id'      => 1,
                'attack_type'              => 'attack',
                'move_down_the_list_every' => null,
            ]);

            $progressBar->advance();
        }

        $this->newLine();

        $this->line('Started exploration for: ' . $characters->count() - 1 . ' watch Horizon.');

        if (!is_null($skipped)) {
            $this->line('Skipped: ' . $skipped);
        }

        return 0;
    }
}
