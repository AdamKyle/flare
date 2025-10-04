<?php

namespace App\Game\BattleRewardProcessing\Jobs;

use App\Flare\Models\Character;
use App\Flare\Models\Monster;
use App\Game\BattleRewardProcessing\Handlers\GoldMinesRewardHandler;
use App\Game\BattleRewardProcessing\Handlers\PurgatorySmithHouseRewardHandler;
use App\Game\BattleRewardProcessing\Handlers\TheOldChurchRewardHandler;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BattleLocationHandler implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private int $characterId;

    private int $monsterId;

    public function __construct(int $characterId, int $monsterId)
    {
        $this->characterId = $characterId;
        $this->monsterId = $monsterId;
    }

    /**
     * Handle the job
     */
    public function handle(
        PurgatorySmithHouseRewardHandler $purgatorySmithHouseRewardHandler,
        GoldMinesRewardHandler $goldMinesRewardHandler,
        TheOldChurchRewardHandler $theOldChurchRewardHandler,
    ): void {
        $character = Character::find($this->characterId);
        $monster = Monster::find($this->monsterId);

        if (is_null($character)) {
            return;
        }

        if (is_null($monster)) {
            return;
        }

        $this->processLocationBasedRewards($character, $monster, $purgatorySmithHouseRewardHandler, $goldMinesRewardHandler, $theOldChurchRewardHandler);
    }

    /**
     * Process location based rewards
     *
     * - Handle Purgatory Smithhouse
     * - Handle Goldmines
     * - Handle The Old Church
     */
    private function processLocationBasedRewards(
        Character $character,
        Monster $monster,
        PurgatorySmithHouseRewardHandler $purgatorySmithHouseRewardHandler,
        GoldMinesRewardHandler $goldMinesRewardHandler,
        TheOldChurchRewardHandler $theOldChurchRewardHandler,
    ): void {
        $character = $purgatorySmithHouseRewardHandler->handleFightingAtPurgatorySmithHouse($character, $monster);

        $character = $goldMinesRewardHandler->handleFightingAtGoldMines($character, $monster);

        $theOldChurchRewardHandler->handleFightingAtTheOldChurch($character, $monster);
    }
}
