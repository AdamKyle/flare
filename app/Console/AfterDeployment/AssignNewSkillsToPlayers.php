<?php

namespace App\Console\AfterDeployment;

use App\Flare\Builders\CharacterBuilder;
use App\Flare\Models\Character;
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
    public function handle() {

        $bar = $this->output->createProgressBar(Character::count());
        $bar->start();

        Character::chunkById(100, function($characters) use($bar) {
            foreach ($characters as $character) {
                $this->assignNewSkills($character);

                $bar->advance();
            }
        });

        $bar->finish();
    }

    public function assignNewSkills(Character $character) {

        $characterBuilder = resolve(CharacterBuilder::class);

        $characterBuilder->setCharacter($character)->assignSkills();
        $characterBuilder->setCharacter($character)->assignPassiveSkills();
    }
}
