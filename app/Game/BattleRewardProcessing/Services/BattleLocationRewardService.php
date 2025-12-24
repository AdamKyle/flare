<?php

namespace App\Game\BattleRewardProcessing\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Monster;
use App\Game\BattleRewardProcessing\Handlers\GoldMinesRewardHandler;
use App\Game\BattleRewardProcessing\Handlers\PurgatorySmithHouseRewardHandler;
use App\Game\BattleRewardProcessing\Handlers\TheOldChurchRewardHandler;

class BattleLocationRewardService {

    /**
     * @var Character|null $character
     */
    protected ?Character $character;

    /**
     * @var Monster|null $monster
     */
    private ?Monster $monster;

    /**
     * @param PurgatorySmithHouseRewardHandler $purgatorySmithHouseRewardHandler
     * @param GoldMinesRewardHandler $goldMinesRewardHandler
     * @param TheOldChurchRewardHandler $theOldChurchRewardHandler
     */
    public function __construct(
        private readonly PurgatorySmithHouseRewardHandler $purgatorySmithHouseRewardHandler,
        private readonly GoldMinesRewardHandler $goldMinesRewardHandler,
        private readonly TheOldChurchRewardHandler $theOldChurchRewardHandler
    ) {

    }

    /**
     * Sert the context for the location reward service
     *
     * @param Character $character
     * @param Monster $monster
     * @return BattleLocationRewardService
     */
    public function setContext(Character $character, Monster $monster): BattleLocationRewardService {
        $this->character = $character;
        $this->monster = $monster;

        return $this;
    }

    /**
     * Handle specific location rewards.
     *
     * @param int $killCount
     * @return Character
     */
    public function handleLocationSpecificRewards(int $killCount = 1): Character {
        $this->character = $this->purgatorySmithHouseRewardHandler->handleFightingAtPurgatorySmithHouse($this->character, $this->monster, $killCount);

        $this->character = $this->goldMinesRewardHandler->handleFightingAtGoldMines($this->character, $this->monster, $killCount);

        return $this->theOldChurchRewardHandler->handleFightingAtTheOldChurch($this->character, $this->monster, $killCount);
    }
}