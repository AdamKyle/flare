<?php

namespace App\Console\AfterDeployment;

use App\Flare\Models\Character;
use App\Game\Factions\FactionLoyalty\Services\UpdateFactionLoyaltyService;
use Illuminate\Console\Command;

class UpdateCharacterFactionBounties extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:character-faction-bounties {characterId}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update a specific characters faction bounties';

    /**
     * Execute the console command.
     */
    public function handle(UpdateFactionLoyaltyService $updateFactionLoyaltyService) {
        $characterId = $this->argument('characterId');

        if (is_null($characterId)) {
            $this->error('Missing character id.');

            return;
        }

        $character = Character::find($characterId);

        if (is_null($character)) {
            $this->error('Character not found.');

            return;
        }

        $updateFactionLoyaltyService->updateFactionLoyaltyBountyTasks($character);
    }
}
