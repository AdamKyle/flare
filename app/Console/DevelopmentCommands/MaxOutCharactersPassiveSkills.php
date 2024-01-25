<?php

namespace App\Console\DevelopmentCommands;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterPassiveSkill;
use App\Flare\Models\Kingdom;
use Illuminate\Console\Command;

class MaxOutCharactersPassiveSkills extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'max:out-characters-passive-skills {characterName}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Maxes out a characters passive skills';

    /**
     * Execute the console command.
     */
    public function handle() {
        $characterName = $this->argument('characterName');

        $character = Character::where('name', $characterName)->first();

        if (is_null($character)) {
            $this->error('No character with that name found.');

            return;
        }

        $characterPassives = CharacterPassiveSkill::where('character_id', $character->id)->get();

        if (empty($characterPassives)) {
            $this->error('How does this character have no passives?');

            return;
        }

        foreach ($characterPassives as $passive) {
            $passive->update([
                'current_level' => $passive->passiveSkill->max_level,
                'is_locked' => false,
            ]);
        }

        $kingdoms = Kingdom::where('character_id', $character->id)->get();

        foreach ($kingdoms as $kingdom) {
            foreach ($kingdom->buildings as $building) {
                $building->update([
                    'is_locked' => false,
                    'level' => $building->gameBuilding->max_level
                ]);
            }
        }
    }
}
