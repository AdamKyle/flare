<?php

namespace App\Game\BattleRewardProcessing\Services;

use App\Game\BattleRewardProcessing\Jobs\BattleCurrenciesHandler;
use App\Game\BattleRewardProcessing\Jobs\BattleFactionHandler;
use App\Game\BattleRewardProcessing\Jobs\BattleGlobalEventHandler;
use App\Game\BattleRewardProcessing\Jobs\BattleItemHandler;
use App\Game\BattleRewardProcessing\Jobs\BattleLocationHandler;
use App\Game\BattleRewardProcessing\Jobs\BattleWeeklyFightHandler;
use App\Game\BattleRewardProcessing\Jobs\BattleXpHandler;

class BattleRewardService
{

    /**
     * @var integer $characterId
     */
    private int $characterId;

    /**
     * @var integer $monsterId
     */
    private int $monsterId;

    /**
     * Set up the battle reward service
     *
     * @param integer $characterId
     * @param integer $monsterId
     * @return BattleRewardService
     */
    public function setUp(int $characterId, int $monsterId): BattleRewardService
    {

        $this->characterId = $characterId;
        $this->monsterId = $monsterId;

        return $this;
    }

    public function handleBaseRewards()
    {

        BattleXpHandler::dispatch($this->characterId, $this->monsterId)->onQueue('battle_reward_xp')->delay(now()->addSeconds(2));
        BattleCurrenciesHandler::dispatch($this->characterId, $this->monsterId)->onQueue('battle_reward_currencies')->delay(now()->addSeconds(2));
        BattleFactionHandler::dispatch($this->characterId, $this->monsterId)->onQueue('battle_reward_factions')->delay(now()->addSeconds(2));
        BattleGlobalEventHandler::dispatch($this->characterId)->onQueue('battle_reward_global_event')->delay(now()->addSeconds(2));
        BattleLocationHandler::dispatch($this->characterId, $this->monsterId)->onQueue('battle_reward_location_handlers')->delay(now()->addSeconds(2));
        BattleWeeklyFightHandler::dispatch($this->characterId, $this->monsterId)->onQueue('battle_reward_weekly_fights')->delay(now()->addSeconds(2));
        BattleItemHandler::dispatch($this->characterId, $this->monsterId)->onQueue('battle_reward_item_handler')->delay(now()->addSeconds(2));
    }
}
