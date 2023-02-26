<?php

namespace App\Console\DevelopmentCommands;

use App\Flare\Models\Character;
use App\Game\Core\Services\CharacterService;
use Illuminate\Console\Command;

class LevelCharacter extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'level:character {id} {levels}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Level a character';

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
     * @return int
     */
    public function handle(CharacterService $characterService)
    {
        $character = Character::find($this->argument('id'));

        if (is_null($character)) {
            return $this->error('Character not found.');
        }

        $bar = $this->output->createProgressBar($this->argument('levels'));

        $bar->start();

        for ($i = 1; $i <= $this->argument('levels'); $i++) {
            $characterService->levelUpCharacter($character, 0);

            $bar->advance();
        }

        $bar->finish();

        $this->newLine(1);

        $this->line('All Done! Character leveled to: ' . $this->argument('levels') + 1);
    }
}
