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
    protected $signature = 'give:skill {skillId}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assigns skill to missing players';

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
        $foundSkill = GameSkill::find($this->argument('skillId'));

        if (is_null($foundSkill)) {
            $this->error('No skill found for that id.');

            return;
        }

        foreach (Character::all() as $character) {
            $hasSkill = $character->skills()->where('game_skill_id', $foundSkill->id)->first();

            if (is_null($hasSkill)) {
                $character->skills()->create(
                    resolve(BaseSkillValue::class)->getBaseCharacterSkillValue($character, $foundSkill)
                );

                $this->line('Gave Character id: ' . $character->id . ' skill.');
            } else {
                $this->line('Character id: ' . $character->id . ' Already has said skill.');
            }
        }
    }
}
