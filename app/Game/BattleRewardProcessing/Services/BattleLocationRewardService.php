<?php

namespace App\Game\BattleRewardProcessing\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Monster;
use App\Game\BattleRewardProcessing\Handlers\GoldMinesRewardHandler;
use App\Game\BattleRewardProcessing\Handlers\PurgatorySmithHouseRewardHandler;
use App\Game\BattleRewardProcessing\Handlers\TheOldChurchRewardHandler;

class BattleLocationRewardService
{
    protected ?Character $character;

    private ?Monster $monster;

    public function __construct(
        private readonly PurgatorySmithHouseRewardHandler $purgatorySmithHouseRewardHandler,
        private readonly GoldMinesRewardHandler $goldMinesRewardHandler,
        private readonly TheOldChurchRewardHandler $theOldChurchRewardHandler
    ) {}

    /**
     * Sert the context for the location reward service
     */
    public function setContext(Character $character, Monster $monster): BattleLocationRewardService
    {
        $this->character = $character;
        $this->monster = $monster;

        return $this;
    }

    /**
     * Handle specific location rewards.
     */
    public function handleLocationSpecificRewards(int $killCount = 1): Character
    {
        $this->character = $this->purgatorySmithHouseRewardHandler->handleFightingAtPurgatorySmithHouse($this->character, $this->monster, $killCount);

        $this->character = $this->goldMinesRewardHandler->handleFightingAtGoldMines($this->character, $this->monster, $killCount);

        return $this->theOldChurchRewardHandler->handleFightingAtTheOldChurch($this->character, $this->monster, $killCount);
    }
}
