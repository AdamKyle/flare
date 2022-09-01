<?php

namespace App\Console\Commands;

use App\Flare\Models\Character;
use Illuminate\Console\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;

class AssignSkillTypesToPlayersSkills extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'assign:skill-type-to-skills';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assigns base skill types to the characters skills.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void {
        $progressBar = new ProgressBar(new ConsoleOutput(), Character::count());

        Character::chunkById(100, function($characters) use($progressBar) {
           foreach ($characters as $character) {

               $this->assignSkillTypeToSkills($character);

               $progressBar->advance();
           }
        });

        $progressBar->finish();
    }

    /**
     * Assigns the base skill type to the skill.
     *
     * @param Character $character
     * @return void
     */
    protected function assignSkillTypeToSkills(Character $character) {
        $skills = $character->skills;

        foreach ($skills as $skill) {
            $skill->update([
                'skill_type' => $skill->baseSkill->type
            ]);
        }
    }
}
