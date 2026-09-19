<?php

namespace App\Game\BattleRewardProcessing\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Monster;
use App\Game\BattleRewardProcessing\Handlers\GoldMinesRewardHandler;
use App\Game\BattleRewardProcessing\Handlers\PurgatorySmithHouseRewardHandler;
use App\Game\BattleRewardProcessing\Handlers\TheOldChurchRewardHandler;
use App\Game\BattleRewardProcessing\Values\BattleRewardSharedContext;

class BattleLocationRewardService
{
    private ?Character $character;

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
    ) {}

    /**
     * Set the Character and Monster used by direct location reward handling.
     *
     * @param Character $character
     * @param Monster $monster
     * @return BattleLocationRewardService
     */
    public function setContext(Character $character, Monster $monster): BattleLocationRewardService
    {
        $this->character = $character;
        $this->monster = $monster;

        return $this;
    }

    /**
     * Plan and apply the current Character's applicable special-location reward.
     *
     * @param int $killCount
     * @return array
     */
    public function handleLocationSpecificRewards(int $killCount = 1): array
    {
        $plan = $this->planLocationReward($this->character, $this->monster, [
            'kill_count' => $killCount,
        ]);

        $result = $this->applyPlannedLocationReward($this->character, $plan);

        return $result['currencies'] ?? [];
    }

    /**
     * Plan the special-Location reward for the current fight, without applying it.
     *
     * @param Character $character
     * @param Monster $monster
     * @param array $context
     * @param ?BattleRewardSharedContext $sharedContext
     * @return array
     */
    public function planLocationReward(Character $character, Monster $monster, array $context = [], ?BattleRewardSharedContext $sharedContext = null): array
    {
        $killCount = $context['kill_count'] ?? 1;

        if (! is_null($sharedContext)) {
            return $this->planLocationRewardFromSharedContext($character, $monster, $killCount, $context, $sharedContext);
        }

        $plan = $this->purgatorySmithHouseRewardHandler->planFightingAtPurgatorySmithHouse($character, $monster, $killCount, $context);

        if ($plan['applies']) {
            return $plan;
        }

        $plan = $this->goldMinesRewardHandler->planFightingAtGoldMines($character, $monster, $killCount, $context);

        if ($plan['applies']) {
            return $plan;
        }

        $plan = $this->theOldChurchRewardHandler->planFightingAtTheOldChurch($character, $monster, $killCount, $context);

        if ($plan['applies']) {
            return $plan;
        }

        return $this->noLocationRewardPlan($character, $monster, $killCount, $context);
    }

    /**
     * Route directly to the single matching Location handler using the
     * request's already-resolved Location type, instead of probing all three
     * handlers' own coordinate lookups.
     *
     * @param Character $character
     * @param Monster $monster
     * @param int $killCount
     * @param array $context
     * @param BattleRewardSharedContext $sharedContext
     * @return array
     */
    private function planLocationRewardFromSharedContext(Character $character, Monster $monster, int $killCount, array $context, BattleRewardSharedContext $sharedContext): array
    {
        $locationType = $sharedContext->locationType();

        if (is_null($locationType)) {
            return $this->noLocationRewardPlan($character, $monster, $killCount, $context);
        }

        if ($locationType->isPurgatorySmithHouse()) {
            return $this->purgatorySmithHouseRewardHandler->planFightingAtPurgatorySmithHouse($character, $monster, $killCount, $context, $sharedContext);
        }

        if ($locationType->isGoldMines()) {
            return $this->goldMinesRewardHandler->planFightingAtGoldMines($character, $monster, $killCount, $context, $sharedContext);
        }

        if ($locationType->isTheOldChurch()) {
            return $this->theOldChurchRewardHandler->planFightingAtTheOldChurch($character, $monster, $killCount, $context, $sharedContext);
        }

        return $this->noLocationRewardPlan($character, $monster, $killCount, $context);
    }

    /**
     * Build the no-op plan for a Character not currently at a special reward Location.
     *
     * @param Character $character
     * @param Monster $monster
     * @param int $killCount
     * @param array $context
     * @return array
     */
    private function noLocationRewardPlan(Character $character, Monster $monster, int $killCount, array $context): array
    {
        return [
            'handler' => 'none',
            'applies' => false,
            'noop' => true,
            'reason' => 'no_location_reward',
            'request_id' => $context['request_id'] ?? null,
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'kill_count' => $killCount,
            'location' => [
                'x' => $character->map?->character_position_x,
                'y' => $character->map?->character_position_y,
                'game_map_id' => $character->map?->game_map_id,
            ],
        ];
    }

    /**
     * Apply an already-planned special-Location reward for the Character.
     *
     * @param Character $character
     * @param array $plan
     * @return array
     */
    public function applyPlannedLocationReward(Character $character, array $plan): array
    {
        return match ($plan['handler'] ?? 'none') {
            'purgatory_smith_house' => $this->purgatorySmithHouseRewardHandler->applyPlannedPurgatorySmithHouseReward($character, $plan),
            'gold_mines' => $this->goldMinesRewardHandler->applyPlannedGoldMinesReward($character, $plan),
            'the_old_church' => $this->theOldChurchRewardHandler->applyPlannedTheOldChurchReward($character, $plan),
            default => [
                'noop' => true,
                'currencies' => [],
                'item_count' => 0,
                'event_created' => false,
            ],
        };
    }
}
