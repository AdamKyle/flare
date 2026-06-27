<?php

namespace App\Game\Skills\Console\Commands;

use App\Flare\Models\Character;
use App\Game\Character\CharacterCreation\Pipeline\Steps\SkillAssigner;
use Illuminate\Console\Command;

class AssignNewSkillsToPlayers extends Command
{
    protected $signature = 'assign:new-skills';

    protected $description = 'Assign new skills';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $bar = $this->output->createProgressBar(Character::count());
        $bar->start();

        Character::chunkById(100, function ($characters) {
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
        $characterBuilder = resolve(CharacterBuilderService::class);

        $characterBuilder->setCharacter($character)->assignSkills();
        $characterBuilder->setCharacter($character)->assignPassiveSkills();
    }
}
