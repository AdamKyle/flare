<?php

namespace App\Game\Character\Console\Commands;

use App\Flare\Models\Character;
use App\Game\Character\CharacterCreation\Pipeline\Steps\SkillAssigner;
use App\Game\Character\CharacterCreation\State\CharacterBuildState;
use Illuminate\Console\Command;

class AssignNewSkillsToPlayers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'assign:new-skills';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign new skills';

    /**
     * Execute the console command.
     */
    public function handle(SkillAssigner $skillAssigner): int
    {
        $progressBar = $this->output->createProgressBar(Character::count());
        $progressBar->start();

        Character::chunkById(100, function ($characters) use ($progressBar, $skillAssigner) {
            foreach ($characters as $character) {
                $this->assignNewSkills($character, $skillAssigner);
                $progressBar->advance();
            }
        });

        $progressBar->finish();

        return self::SUCCESS;
    }

    private function assignNewSkills(Character $character, SkillAssigner $skillAssigner): void
    {
        $state = new CharacterBuildState();
        $state->setCharacter($character);

        $skillAssigner->process($state, function (CharacterBuildState $s) {
            return $s;
        });
    }
}
