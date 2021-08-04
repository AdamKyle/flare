<?php

namespace App\Console\Commands;

use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Flare\Models\ReleaseNote;
use App\Flare\Values\BaseSkillValue;
use GitHub;
use Carbon\Carbon;
use Illuminate\Console\Command;


class GiveCharactersSkill extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'give:skills';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assigns skills to missing players';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        $gameSkills = GameSkill::all();

        if ($gameSkills->isEmpty()) {
            $this->error('No skills in the system.');

            return;
        }

        forEach($gameSkills as $skill) {
            foreach (Character::all() as $character) {
                $hasSkill = $character->skills()->where('game_skill_id', $skill->id)->first();

                if (is_null($hasSkill)) {
                    if (!is_null($skill->gameClass)) {
                        if ($character->class->id === $skill->game_class_id) {
                            $character->skills()->create(
                                resolve(BaseSkillValue::class)->getBaseCharacterSkillValue($character, $skill)
                            );

                            $this->line('Gave Character id: ' . $character->name . ' class skill: ' . $skill->name);
                        }
                    } else {
                        $character->skills()->create(
                            resolve(BaseSkillValue::class)->getBaseCharacterSkillValue($character, $skill)
                        );

                        $this->line('Gave Character id: ' . $character->name . ' skill: ' . $skill->name);
                    }

                } else {
                    $this->line('Character id: ' . $character->name . ' Already has skill: ' . $skill->name);
                }
            }
        }

    }
}
