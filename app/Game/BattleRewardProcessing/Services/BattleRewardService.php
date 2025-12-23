<?php

namespace App\Game\BattleRewardProcessing\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Monster;
use App\Flare\Services\CharacterRewardService;
use App\Game\BattleRewardProcessing\Handlers\BattleMessageHandler;
use App\Game\BattleRewardProcessing\Handlers\FactionHandler;
use App\Game\BattleRewardProcessing\Handlers\FactionLoyaltyBountyHandler;
use App\Game\BattleRewardProcessing\Jobs\BattleCurrenciesHandler;
use App\Game\BattleRewardProcessing\Jobs\BattleFactionHandler;
use App\Game\BattleRewardProcessing\Jobs\BattleGlobalEventHandler;
use App\Game\BattleRewardProcessing\Jobs\BattleItemHandler;
use App\Game\BattleRewardProcessing\Jobs\BattleLocationHandler;
use App\Game\BattleRewardProcessing\Jobs\BattleSecondaryRewardHandler;
use App\Game\BattleRewardProcessing\Jobs\BattleWeeklyFightHandler;
use App\Game\BattleRewardProcessing\Jobs\BattleXpHandler;
use App\Game\BattleRewardProcessing\Jobs\Events\WinterEventChristmasGiftHandler;
use App\Game\Core\Services\GoldRush;
use App\Game\Factions\FactionLoyalty\Events\FactionLoyaltyUpdate;
use App\Game\Factions\FactionLoyalty\Services\FactionLoyaltyService;
use Exception;
use Throwable;

class BattleRewardService
{

    /**
     * @var Character $characterId
     */
    private Character $character;

    /**
     * @var Monster $monsterId
     */
    private Monster $monster;

    /**
     * @var array $context
     */
    private array $context;

    public function __construct(
        private readonly BattleMessageHandler $battleMessageHandler,
        private readonly CharacterRewardService $characterRewardService,
        private readonly FactionHandler $factionHandler,
        private readonly FactionLoyaltyBountyHandler $factionLoyaltyBountyHandler,
        private readonly FactionLoyaltyService $factionLoyaltyService,
        private readonly GoldRush $goldRush,
    ) {}

    /**
     * Set up the battle reward service
     *
     * @param integer $characterId
     * @param integer $monsterId
     * @return BattleRewardService
     */
    public function setUp(int $characterId, int $monsterId): BattleRewardService
    {

        $this->character = Character::find($characterId);
        $this->monster = Monster::find($monsterId);

        return $this;
    }

    public function setContext(array $context): BattleRewardService {
        $this->context = $context;

        return $this;
    }

    /**
     * @return void
     * @throws Exception
     * @throws Throwable
     */
    public function processRewards(): void {
        $this->handleAwardingXP();
        $this->handleFactionPoints();
        $this->handleFactionLoyaltyBounty();
        $this->handleCurrencyRewards();
    }

    /**
     * Handle awarding XP and Skill XP
     *
     * @return void
     * @throws Exception
     */
    private function handleAwardingXP(): void {
        if (!isset($this->context['total_xp']) && !isset($this->context['total_creatures'])) {
            $this->characterRewardService->setCharacter($this->character)
                ->distributeCharacterXP($this->monster)
                ->distributeSkillXP($this->monster);

            $this->character = $this->character->refresh();

            return;
        }

        $totalXP = $this->context['total_xp'];
        $totalCreatures = $this->context['total_creatures'];

        $user = $this->character->user;

        $this->battleMessageHandler->handleMessageForExplorationXp($user, $totalCreatures, $totalXP);

        $this->characterRewardService->setCharacter($this->character)->distributeSpecifiedXp($totalXP);

        $this->character = $this->character->refresh();
    }

    /**
     * Handle awarding faction points.
     *
     * @return void
     * @throws Throwable
     */
    private function handleFactionPoints(): void {

        $gameMap = $this->character->map->gameMap;

        if ($gameMap->mapType()->isPurgatory()) {
            return;
        }

        if ($this->character->is_auto_battling) {
            return;
        }

        if (!isset($this->context['total_faction_points'])) {
            $this->factionHandler->handleFaction($this->character, $this->monster);

            $this->character = $this->character->refresh();

            return;
        }

        $totalPoints = $this->context['total_faction_points'];

        $this->factionHandler->awardFactionPointsFromBatch($this->character, $totalPoints);

        $this->character = $this->character->refresh();
    }

    /**
     * Handles Faction Bounties.
     *
     * @return void
     */
    private function handleFactionLoyaltyBounty(): void {
        $gameMap = $this->character->map->gameMap;

        if ($gameMap->mapType()->isPurgatory()) {
            return;
        }

        if (!isset($this->context['total_creatures'])) {
            $this->factionLoyaltyBountyHandler->handleBounty($this->character, $this->monster);

            $this->character = $this->character->refresh();

            return;
        }

        $totalCreatures = $this->context['total_creatures'];

        $this->factionLoyaltyBountyHandler->handleBounty($this->character, $this->monster, $totalCreatures);

        $this->character = $this->character->refresh();

        event(new FactionLoyaltyUpdate($this->character->user, $this->factionLoyaltyService->getLoyaltyInfoForPlane($this->character)));
    }

    private function handleCurrencyRewards(): void {
        $totalKills = 1;

        if (isset($this->context['total_kills'])) {
            $totalKills = $this->context['total_kills'];
        }

        $this->characterRewardService->setCharacter($this->character)->giveCurrencies($this->monster, $totalKills);

        $this->character = $this->character->refresh();
    }

    public function handleBaseRewards($includeXp = true, $includeEventRewards = true, $includeFactionReward = true)
    {

        if ($includeXp) {
            BattleXpHandler::dispatch($this->characterId, $this->monsterId)->onConnection('battle_reward_xp')->onQueue('battle_reward_xp')->delay(now()->addSeconds(2));
        }

        if ($includeEventRewards) {
            WinterEventChristmasGiftHandler::dispatch($this->characterId)->onConnection('event_battle_reward')->onQueue('event_battle_reward')->delay(now()->addSeconds(2));
        }

        if ($includeFactionReward) {
            BattleFactionHandler::dispatch($this->characterId, $this->monsterId)->onConnection('battle_reward_factions')->onQueue('battle_reward_factions')->delay(now()->addSeconds(2));
        }

        BattleSecondaryRewardHandler::dispatch($this->characterId)->onConnection('battle_secondary_reward')->onQueue('battle_secondary_reward')->delay(now()->addSeconds(2));
        BattleCurrenciesHandler::dispatch($this->characterId, $this->monsterId)->onConnection('battle_reward_currencies')->onQueue('battle_reward_currencies')->delay(now()->addSeconds(2));
        BattleGlobalEventHandler::dispatch($this->characterId)->onConnection('battle_reward_global_event')->onQueue('battle_reward_global_event')->delay(now()->addSeconds(2));
        BattleLocationHandler::dispatch($this->characterId, $this->monsterId)->onConnection('battle_reward_location_handlers')->onQueue('battle_reward_location_handlers')->delay(now()->addSeconds(2));
        BattleWeeklyFightHandler::dispatch($this->characterId, $this->monsterId)->onConnection('battle_reward_weekly_fights')->onQueue('battle_reward_weekly_fights')->delay(now()->addSeconds(2));
        BattleItemHandler::dispatch($this->characterId, $this->monsterId)->onConnection('battle_reward_item_handler')->onQueue('battle_reward_item_handler')->delay(now()->addSeconds(2));
    }
}
