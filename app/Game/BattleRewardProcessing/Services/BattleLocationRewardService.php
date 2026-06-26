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
     * @return array
     */
    public function handleLocationSpecificRewards(int $killCount = 1): array {
        $plan = $this->planLocationReward($this->character, $this->monster, [
            'kill_count' => $killCount,
        ]);

        $result = $this->applyPlannedLocationReward($this->character, $plan);

        return $result['currencies'] ?? [];
    }

    public function planLocationReward(Character $character, Monster $monster, array $context = []): array
    {
        $killCount = (int) ($context['kill_count'] ?? 1);

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
