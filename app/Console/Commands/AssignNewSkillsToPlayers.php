<?php

namespace App\Console\Commands;

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
    public function handle()
    {
        Character::chunkById(100, function($characters) {
            foreach ($characters as $character) {
                $this->assignNewSkills($character);
            }
        });
    }

    public function assignNewSkills(Character $character) {
        resolve(CharacterBuilder::class)->setCharacter($character)->assignSkills();
    }
}
