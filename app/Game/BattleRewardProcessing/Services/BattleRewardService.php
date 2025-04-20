<?php

namespace App\Game\BattleRewardProcessing\Services;

use App\Game\BattleRewardProcessing\Jobs\BattleCurrenciesHandler;
use App\Game\BattleRewardProcessing\Jobs\BattleFactionHandler;
use App\Game\BattleRewardProcessing\Jobs\BattleGlobalEventHandler;
use App\Game\BattleRewardProcessing\Jobs\BattleItemHandler;
use App\Game\BattleRewardProcessing\Jobs\BattleLocationHandler;
use App\Game\BattleRewardProcessing\Jobs\BattleSecondaryRewardHandler;
use App\Game\BattleRewardProcessing\Jobs\BattleWeeklyFightHandler;
use App\Game\BattleRewardProcessing\Jobs\BattleXpHandler;
use App\Game\BattleRewardProcessing\Jobs\Events\WinterEventChristmasGiftHandler;

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

    public function handleBaseRewards($includeXp = true, $includeEventRewards = true)
    {

        if ($includeXp) {
            BattleXpHandler::dispatch($this->characterId, $this->monsterId)->onConnection('battle_reward_xp')->onQueue('battle_reward_xp')->delay(now()->addSeconds(2));
        }

        if ($includeEventRewards) {
            WinterEventChristmasGiftHandler::dispatch($this->characterId)->onConnection('event_battle_reward')->onQueue('event_battle_reward')->delay(now()->addSeconds(2));
        }

        BattleFactionHandler::dispatch($this->characterId, $this->monsterId)->onConnection('battle_reward_factions')->onQueue('battle_reward_factions')->delay(now()->addSeconds(2));
        BattleSecondaryRewardHandler::dispatch($this->characterId)->onConnection('battle_secondary_reward')->onQueue('battle_secondary_reward')->delay(now()->addSeconds(2));
        BattleCurrenciesHandler::dispatch($this->characterId, $this->monsterId)->onConnection('battle_reward_currencies')->onQueue('battle_reward_currencies')->delay(now()->addSeconds(2));
        BattleGlobalEventHandler::dispatch($this->characterId)->onConnection('battle_reward_global_event')->onQueue('battle_reward_global_event')->delay(now()->addSeconds(2));
        BattleLocationHandler::dispatch($this->characterId, $this->monsterId)->onConnection('battle_reward_location_handlers')->onQueue('battle_reward_location_handlers')->delay(now()->addSeconds(2));
        BattleWeeklyFightHandler::dispatch($this->characterId, $this->monsterId)->onConnection('battle_reward_weekly_fights')->onQueue('battle_reward_weekly_fights')->delay(now()->addSeconds(2));
        BattleItemHandler::dispatch($this->characterId, $this->monsterId)->onConnection('battle_reward_item_handler')->onQueue('battle_reward_item_handler')->delay(now()->addSeconds(2));
    }
}
