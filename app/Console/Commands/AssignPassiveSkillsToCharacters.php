<?php

namespace App\Console\Commands;

use App\Flare\Models\Character;
use App\Flare\Models\PassiveSkill;
use Illuminate\Console\Command;

class AssignPassiveSkillsToCharacters extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'assign:passive-skills';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
            foreach($characters as $character) {
                $this->assignPassive($character);
            }
        });
    }

    /**
     * Assign the passive skill.
     *
     * @param Character $character
     */
    protected function assignPassive(Character $character) {
        foreach (PassiveSkill::all() as $passiveSkill) {
            $characterPassive = $character->passiveSkills()->where('passive_skill_id', $passiveSkill->id)->first();

            if (is_null($characterPassive)) {
                $parentId = $passiveSkill->parent_skill_id;
                $parent   = null;

                if (!is_null($parentId)) {
                    $parent = $character->passiveSkills()->where('passive_skill_id', $parentId)->first();
                }

                $character->passiveSkills()->create([
                    'character_id'     => $character->id,
                    'passive_skill_id' => $passiveSkill->id,
                    'current_level'    => 0,
                    'hours_to_next'    => $passiveSkill->hours_per_level,
                    'is_locked'        => $passiveSkill->is_locked,
                    'parent_skill_id'  => !is_null($parent) ? $parent->id : null,
                ]);
            } else {
                $characterPassive->update([
                    'hours_to_next'    => $passiveSkill->hours_per_level,
                ]);
            }

            $character = $character->refresh();
        }
    }
}
