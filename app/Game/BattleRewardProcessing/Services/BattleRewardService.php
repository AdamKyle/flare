<?php

namespace App\Game\BattleRewardProcessing\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Event;
use App\Flare\Models\GlobalEventGoal;
use App\Flare\Models\Monster;
use App\Flare\Services\CharacterRewardService;
use App\Game\BattleRewardProcessing\Handlers\BattleGlobalEventParticipationHandler;
use App\Game\Events\Values\EventType;
use App\Game\Events\Values\GlobalEventSteps;
use App\Game\BattleRewardProcessing\Handlers\BattleMessageHandler;
use App\Game\BattleRewardProcessing\Handlers\FactionHandler;
use App\Game\BattleRewardProcessing\Handlers\FactionLoyaltyBountyHandler;
use App\Game\BattleRewardProcessing\Jobs\Events\WinterEventChristmasGiftHandler;
use App\Game\Core\Services\DropCheckService;
use App\Game\Core\Services\GoldRush;
use App\Game\Core\Traits\SafelyBroadcastsEvents;
use App\Game\Factions\FactionLoyalty\Events\FactionLoyaltyUpdate;
use App\Game\Factions\FactionLoyalty\Services\FactionLoyaltyService;
use App\Flare\Models\ExplorationLog;
use App\Game\Automation\Services\ExplorationLogService;
use App\Game\Skills\Services\SkillService;
use Exception;
use Throwable;

class BattleRewardService
{
    use SafelyBroadcastsEvents;


    /**
     * @var ?Character $characterId
     */
    private ?Character $character;

    /**
     * @var ?Monster $monsterId
     */
    private ?Monster $monster;

    /**
     * @var array $context
     */
    private array $context = [];

    private array $earnedCurrencies = [];

    /**
     * @param BattleMessageHandler $battleMessageHandler
     * @param CharacterRewardService $characterRewardService
     * @param FactionHandler $factionHandler
     * @param FactionLoyaltyBountyHandler $factionLoyaltyBountyHandler
     * @param FactionLoyaltyService $factionLoyaltyService
     * @param GoldRush $goldRush
     * @param BattleLocationRewardService $battleLocationRewardService
     * @param DropCheckService $dropCheckService
     * @param WeeklyBattleService $weeklyBattleService
     * @param SecondaryRewardService $secondaryRewardService
     * @param BattleGlobalEventParticipationHandler $battleGlobalEventParticipationHandler
     * @param SkillService $skillService
     */
    public function __construct(
        private readonly BattleMessageHandler $battleMessageHandler,
        private readonly CharacterRewardService $characterRewardService,
        private readonly FactionHandler $factionHandler,
        private readonly FactionLoyaltyBountyHandler $factionLoyaltyBountyHandler,
        private readonly FactionLoyaltyService $factionLoyaltyService,
        private readonly GoldRush $goldRush,
        private readonly BattleLocationRewardService $battleLocationRewardService,
        private readonly DropCheckService $dropCheckService,
        private readonly WeeklyBattleService $weeklyBattleService,
        private readonly SecondaryRewardService $secondaryRewardService,
        private readonly BattleGlobalEventParticipationHandler $battleGlobalEventParticipationHandler,
        private readonly SkillService $skillService,
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
        $this->earnedCurrencies = [];

        return $this;
    }

    /**
     * Set the context for the service.
     *
     * @param array $context
     * @return $this
     */
    public function setContext(array $context): BattleRewardService {
        $this->context = $context;

        return $this;
    }

    /**
     * @param bool $includeWinterEvent
     * @return void
     * @throws Throwable
     */
    public function processRewards(bool $includeWinterEvent = false): void {

        if (is_null($this->character) || is_null($this->monster)) {
            return;
        }

        $beforeSnapshot = null;

        if (isset($this->context['exploration_log_id'])) {
            $beforeSnapshot = $this->explorationRewardSnapshot();
        }

        $this->handleAwardSkillPoints();
        $this->handleFactionPoints();
        $this->handleFactionLoyaltyBounty();
        $this->handleCurrencyRewards();
        $this->handleSpecificLocationRewards();
        $this->handleItemDrops();
        $this->handleWeeklyFightRewards();
        $this->handleSecondaryRewards();
        $this->handleGlobalEventParticipation();
        $this->handleAwardingXP();

        if (! is_null($beforeSnapshot)) {
            $log = ExplorationLog::find($this->context['exploration_log_id']);

            if (! is_null($log)) {
                $character = $this->character->refresh();

                ExplorationLogService::applyRewardContext(
                    $log,
                    $character,
                    $this->explorationRewardSnapshotWithEarnedCurrencies($beforeSnapshot, $character),
                    $this->context
                );
            }
        }

        if ($includeWinterEvent) {
            WinterEventChristmasGiftHandler::dispatch($this->character->id)->onConnection('event_battle_reward')->onQueue('event_battle_reward')->delay(now()->addSeconds(2));
        }
    }

    private function explorationRewardSnapshot(): array
    {
        $trainingSkill = $this->character->skills()->where('currently_training', true)->first();
        $gameMapId = $this->character->map?->game_map_id;
        $faction = ! is_null($gameMapId)
            ? $this->character->factions()->where('game_map_id', $gameMapId)->first()
            : null;

        return [
            'xp' => $this->character->xp,
            'skill_id' => $trainingSkill?->id,
            'skill_xp' => $trainingSkill?->xp ?? 0,
            'faction_id' => $faction?->id,
            'faction_points' => $faction?->current_points ?? 0,
            'level' => $this->character->level,
            'gold' => $this->character->gold,
            'gold_dust' => $this->character->gold_dust,
            'shards' => $this->character->shards,
            'copper_coins' => $this->character->copper_coins,
        ];
    }

    private function explorationRewardSnapshotWithEarnedCurrencies(array $beforeSnapshot, Character $character): array
    {
        foreach ($this->earnedCurrencies as $currency => $amount) {
            if ($amount <= 0) {
                continue;
            }

            $beforeSnapshot[$currency] = $character->getAttribute($currency) - $amount;
        }

        return $beforeSnapshot;
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
                ->distributeCharacterXP($this->monster);

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
     * Handle awarding skill experience.
     *
     * @return void
     * @throws Exception
     */
    private function handleAwardSkillPoints(): void {

        if (!isset($this->context['total_skill_xp'])) {
            $this->characterRewardService->setCharacter($this->character)
                ->distributeSkillXP($this->monster);

            $this->character = $this->character->refresh();

            return;
        }

        $totalSkillPoints = $this->context['total_skill_xp'];

        $this->skillService->setSkillInTraining($this->character)->giveXpToTrainingSkill($this->character, $totalSkillPoints);

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

        if (!isset($this->context['total_faction_points'])) {
            if ($this->character->is_auto_battling) {
                return;
            }

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

            $this->sendFactionLoyaltyUpdateEvent();

            return;
        }

        $totalCreatures = $this->context['total_creatures'];

        $this->factionLoyaltyBountyHandler->handleBounty($this->character, $this->monster, $totalCreatures);

        $this->character = $this->character->refresh();

        $this->sendFactionLoyaltyUpdateEvent();
    }

    /**
     * Send the faction loyalty update event.
     *
     * @return void
     */
    private function sendFactionLoyaltyUpdateEvent(): void
    {
        if ($this->context['skip_faction_loyalty_update_event'] ?? false) {
            return;
        }

        $this->safelyDispatchBroadcastEvent(
            new FactionLoyaltyUpdate($this->character->user, $this->factionLoyaltyService->getLoyaltyInfoForPlane($this->character)),
            ['character_id' => $this->character->id]
        );
    }

    /**
     * Handle currency rewards
     *
     * @return void
     * @throws Exception
     */
    private function handleCurrencyRewards(): void {
        $totalKills = 1;

        if (isset($this->context['total_creatures'])) {
            $totalKills = $this->context['total_creatures'];
        }

        $goldBeforeReward = $this->character->gold;

        $this->earnedCurrencies = $this->characterRewardService->setCharacter($this->character)->giveCurrencies($this->monster, $totalKills);

        $character = $this->character->refresh();
        $goldGained = $character->gold - $goldBeforeReward;

        $this->goldRush->processPotentialGoldRush($character, $goldGained);

        $this->character = $character->refresh();
    }

    /**
     * Handle specific location rewards
     *
     * @return void
     */
    private function handleSpecificLocationRewards(): void {
        $totalKills = 1;

        if (isset($this->context['total_creatures'])) {
            $totalKills = $this->context['total_creatures'];
        }

        $earnedLocationCurrencies = $this->battleLocationRewardService
            ->setContext($this->character, $this->monster)
            ->handleLocationSpecificRewards($totalKills);

        foreach ($earnedLocationCurrencies as $currency => $amount) {
            if ($amount > 0) {
                $this->earnedCurrencies[$currency] = ($this->earnedCurrencies[$currency] ?? 0) + $amount;
            }
        }
    }

    /**
     * Process enemy drops.
     *
     * @return void
     * @throws Exception
     */
    private function handleItemDrops(): void {
        $totalKills = 1;

        if (isset($this->context['total_creatures'])) {
            $totalKills = $this->context['total_creatures'];
        }

        $lootingChance = $this->character->skills->where('name', '=', 'Looting')->first()->skill_bonus;

        if ($totalKills > 1) {
            for ($i = 0; $i < $totalKills; $i++) {
                $this->addDropRewardTotals(
                    $this->dropCheckService->process($this->character, $this->monster, $lootingChance)
                );

                $this->character = $this->character->refresh();
            }

            return;
        }

        $this->addDropRewardTotals(
            $this->dropCheckService->process($this->character, $this->monster, $lootingChance)
        );
    }

    private function addDropRewardTotals(array $dropRewardTotals): void
    {
        $autoSoldGold = $dropRewardTotals['auto_sold_gold'] ?? 0;

        if ($autoSoldGold <= 0) {
            return;
        }

        $this->earnedCurrencies['gold'] = ($this->earnedCurrencies['gold'] ?? 0) + $autoSoldGold;
    }

    /**
     * Handle weekly fight rewards, only when not exploring.
     *
     * @return void
     * @throws Exception
     */
    private function handleWeeklyFightRewards(): void {
        if ($this->character->is_auto_battling) {
            return;
        }

        $this->character = $this->weeklyBattleService->handleMonsterDeath($this->character, $this->monster);
    }

    /**
     * Handle secondary rewards.
     *
     * - Class Ranks
     * - Item Skills
     *
     * @return void
     * @throws Exception
     */
    private function handleSecondaryRewards(): void {
        $totalKills = 1;

        if (isset($this->context['total_creatures'])) {
            $totalKills = $this->context['total_creatures'];
        }

        $this->secondaryRewardService->handleSecondaryRewards($this->character, $totalKills);

        $this->character = $this->character->refresh();
    }

    /**
     * Handle event participation.
     *
     * @return void
     * @throws Exception
     */
    private function handleGlobalEventParticipation(): void {
        $gameMap = $this->character->map->gameMap;
        $eventType = $gameMap->only_during_event_type;

        if (is_null($eventType)) {
            return;
        }

        $event = Event::where('type', $eventType)->first();

        if (is_null($event)) {
            return;
        }

        if ($eventType === EventType::DELUSIONAL_MEMORIES_EVENT && $event->current_event_goal_step !== GlobalEventSteps::BATTLE) {
            return;
        }

        $globalEventGoal = GlobalEventGoal::where('event_type', $eventType)->first();

        if (is_null($globalEventGoal)) {
            return;
        }

        if (is_null($globalEventGoal->max_kills)) {
            return;
        }

        $totalKills = isset($this->context['total_creatures']) ? $this->context['total_creatures'] : 1;

        $this->battleGlobalEventParticipationHandler->handleGlobalEventParticipation($this->character, $globalEventGoal, $totalKills);

        $this->character = $this->character->refresh();
    }
}
