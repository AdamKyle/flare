<?php

namespace App\Game\BattleRewardProcessing\Jobs;

use App\Flare\Models\Character;
use App\Flare\Models\Monster;
use App\Game\BattleRewardProcessing\Handlers\FactionHandler;
use App\Game\BattleRewardProcessing\Handlers\FactionLoyaltyBountyHandler;
use App\Game\Factions\FactionLoyalty\Events\FactionLoyaltyUpdate;
use App\Game\Factions\FactionLoyalty\Services\FactionLoyaltyService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BattleFactionHandler implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private int $characterId, private int $monsterId) {}

    /**
     * Handle the job
     */
    public function handle(FactionHandler $factionHandler, FactionLoyaltyBountyHandler $factionLoyaltyBountyHandler, FactionLoyaltyService $factionLoyaltyService): void
    {
        $character = Character::find($this->characterId);
        $monster = Monster::find($this->monsterId);

        if (is_null($character)) {
            return;
        }

        if (is_null($monster)) {
            return;
        }

        $this->handleFactionRewards($character, $monster, $factionHandler);

        $this->handleFactionBounties($character, $monster, $factionLoyaltyBountyHandler);

        event(new FactionLoyaltyUpdate($character->user, $factionLoyaltyService->getLoyaltyInfoForPlane($character)));
    }

    /**
     * Handle updating the faction
     *
     * - This also includes the rewards for factions
     */
    private function handleFactionRewards(Character $character, Monster $monster, FactionHandler $factionHandler): void
    {
        $gameMap = $character->map->gameMap;

        if ($gameMap->mapType()->isPurgatory()) {
            return;
        }

        $factionHandler->handleFaction($character, $monster);
    }

    /**
     * Handle faction bounties
     */
    private function handleFactionBounties(Character $character, Monster $monster, FactionLoyaltyBountyHandler $factionLoyaltyBountyHandler): void
    {
        $factionLoyaltyBountyHandler->handleBounty($character, $monster);
    }
}
