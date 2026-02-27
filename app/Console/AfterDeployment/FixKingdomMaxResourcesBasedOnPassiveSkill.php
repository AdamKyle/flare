<?php

namespace App\Console\AfterDeployment;

use App\Flare\Models\Kingdom;
use App\Game\PassiveSkills\Values\PassiveSkillTypeValue;
use Illuminate\Console\Command;

class FixKingdomMaxResourcesBasedOnPassiveSkill extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:kingdom-max-resources-based-on-passive-skill';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fixes kingdoms to respect bountiful resources';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Kingdom::where('npc_owned', false)->chunk(500, function ($kingdoms) {
            foreach ($kingdoms as $kingdom) {
                $skill = $kingdom->character->passiveSkills->where('passiveSkill.effect_type', PassiveSkillTypeValue::RESOURCE_INCREASE)->first();

                if (is_null($skill)) {
                    return;
                }

                $kingdom->update([
                    'max_stone' => $kingdom->max_stone + $skill->resource_increase_amount,
                    'max_iron' => $kingdom->max_iron + $skill->resource_increase_amount,
                    'max_wood' => $kingdom->max_wood + $skill->resource_increase_amount,
                    'max_clay' => $kingdom->max_clas + $skill->resource_increase_amount,
                    'max_population' => $kingdom->max_population + $skill->resource_increase_amount,
                ]);
            }
        });
    }
}
