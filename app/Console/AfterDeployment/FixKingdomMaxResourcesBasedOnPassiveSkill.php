<?php

namespace App\Console\AfterDeployment;

use App\Flare\Models\Kingdom;
use App\Game\Kingdoms\Service\KingdomMaxResourceRecalculationService;
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
    public function handle(KingdomMaxResourceRecalculationService $kingdomMaxResourceRecalculationService)
    {
        Kingdom::where('npc_owned', false)->chunk(500, function ($kingdoms) use ($kingdomMaxResourceRecalculationService) {
            foreach ($kingdoms as $kingdom) {
                $kingdomMaxResourceRecalculationService->recalculate($kingdom, true);
            }
        });
    }
}
