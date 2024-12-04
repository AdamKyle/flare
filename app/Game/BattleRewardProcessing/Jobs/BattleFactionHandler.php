<?php

namespace App\Game\BattleRewardProcessing\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\Character;
use App\Flare\Models\Monster;
use App\Game\BattleRewardProcessing\Handlers\FactionHandler;
use App\Game\BattleRewardProcessing\Handlers\FactionLoyaltyBountyHandler;

class BattleFactionHandler implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param integer $characterId
     * @param integer $monsterId
     */
    public function __construct(private int $characterId, private int $monsterId) {}

    /**
     * Handle the job
     *
     * @param FactionHandler $factionHandler
     * @param FactionLoyaltyBountyHandler $factionLoyaltyBountyHandler
     * @return void
     */
    public function handle(FactionHandler $factionHandler, FactionLoyaltyBountyHandler $factionLoyaltyBountyHandler): void
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
    }

    /**
     * Handle updating the faction
     *
     * - This also includes the rewards for factions
     *
     * @param Character $character
     * @param Monster $monster
     * @param FactionHandler $factionHandler
     * @return void
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
     *
     * @param Character $character
     * @param Monster $monster
     * @param FactionLoyaltyBountyHandler $factionLoyaltyBountyHandler
     * @return void
     */
    private function handleFactionBounties(Character $character, Monster $monster, FactionLoyaltyBountyHandler $factionLoyaltyBountyHandler): void
    {
        $factionLoyaltyBountyHandler->handleBounty($character, $monster);
    }
}
