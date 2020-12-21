<?php

namespace App\Console\Commands;

use App\Flare\Events\UpdateSkillEvent;
use App\Flare\Models\Character;
use App\Game\Core\Services\CharacterService;
use Illuminate\Console\Command;

class LevelFakeUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'level-up:fake-users {amount} {amountOfLevels}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Levels characters based on how many characters and amount of levels.';

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
     *
     * @return mixed
     */
    public function handle()
    {
        $amount         = $this->argument('amount');
        $amountOfLevels = $this->argument('amountOfLevels');

        if ($amount <= 0) {
            $this->error('amount must be greator then 0.');
            return;
        }

        if ($amountOfLevels <= 0) {
            $this->error('amount of levels must be greator then 0.');
            return;
        }

        $this->info('Leveling character');

        $bar = $this->output->createProgressBar($amount);

        $bar->start();

        for ($i = 1; $i <= (int) $amount; $i++) {
            $character        = Character::find($i);
            $characterService = new CharacterService;

            for($j = 1; $j <= $amountOfLevels; $j++) {
                
                $characterService->levelUpCharacter($character);

                $character->refresh();
            }

            $bar->advance();
        }

        $bar->finish();

        $this->info(' All Done :D');
    }
}
