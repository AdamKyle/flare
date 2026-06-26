<?php

namespace App\Game\BattleRewardProcessing\Services;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterBattleRewardRequest;
use App\Flare\Models\CharacterBattleRewardRequestStep;
use App\Flare\Models\Event;
use App\Flare\Models\GlobalEventGoal;
use App\Flare\Models\Monster;
use App\Flare\Services\CharacterRewardService;
use Closure;
use App\Game\BattleRewardProcessing\Enums\BattleRewardStepName;
use App\Game\BattleRewardProcessing\Enums\BattleRewardStepStatus;
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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
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
        private readonly BattleRewardLedgerService $battleRewardLedgerService,
        private readonly BattleRewardMessageContext $battleRewardMessageContext,
    ) {}

    /**
     * Set up the battle reward service
     *
     * @param integer $characterId
     * @param integer $monsterId
     * @return BattleRewardService
     */
    public function withHeartbeatCallback(?Closure $callback): self
    {
        $this->characterRewardService->withHeartbeatCallback($callback);

        return $this;
    }

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
            Log::channel('reward_processing')->debug('processRewards: skipped — character or monster is null.', [
                'character_id' => $this->character?->id,
                'monster_id' => $this->monster?->id,
            ]);

            return;
        }

        Log::channel('reward_processing')->debug('processRewards: entered.', [
            'character_id' => $this->character->id,
            'monster_id' => $this->monster->id,
            'has_exploration_log' => isset($this->context['exploration_log_id']),
        ]);

        $beforeSnapshot = null;

        if (isset($this->context['exploration_log_id'])) {
            $beforeSnapshot = $this->explorationRewardSnapshot();
        }

        Log::channel('reward_processing')->debug('processRewards: handleAwardSkillPoints start.', ['character_id' => $this->character->id]);
        $this->handleAwardSkillPoints();

        Log::channel('reward_processing')->debug('processRewards: handleFactionPoints start.', ['character_id' => $this->character->id]);
        $this->handleFactionPoints();

        Log::channel('reward_processing')->debug('processRewards: handleFactionLoyaltyBounty start.', ['character_id' => $this->character->id]);
        $this->handleFactionLoyaltyBounty();

        Log::channel('reward_processing')->debug('processRewards: handleCurrencyRewards start.', ['character_id' => $this->character->id]);
        $this->handleCurrencyRewards();

        Log::channel('reward_processing')->debug('processRewards: handleSpecificLocationRewards start.', ['character_id' => $this->character->id]);
        $this->handleSpecificLocationRewards();

        Log::channel('reward_processing')->debug('processRewards: handleItemDrops start.', ['character_id' => $this->character->id]);
        $this->handleItemDrops();

        Log::channel('reward_processing')->debug('processRewards: handleWeeklyFightRewards start.', ['character_id' => $this->character->id]);
        $this->handleWeeklyFightRewards();

        Log::channel('reward_processing')->debug('processRewards: handleSecondaryRewards start.', ['character_id' => $this->character->id]);
        $this->handleSecondaryRewards();

        Log::channel('reward_processing')->debug('processRewards: handleGlobalEventParticipation start.', ['character_id' => $this->character->id]);
        $this->handleGlobalEventParticipation();

        Log::channel('reward_processing')->debug('processRewards: handleAwardingXP start.', ['character_id' => $this->character->id]);
        $this->handleAwardingXP();

        Log::channel('reward_processing')->debug('processRewards: all reward steps completed.', ['character_id' => $this->character->id]);

        if (! is_null($beforeSnapshot)) {
            $log = ExplorationLog::find($this->context['exploration_log_id']);

            if (! is_null($log)) {
                $character = $this->character->refresh();

                Log::channel('reward_processing')->debug('processRewards: applying exploration reward context.', [
                    'character_id' => $character->id,
                    'exploration_log_id' => $this->context['exploration_log_id'],
                ]);

                ExplorationLogService::applyRewardContext(
                    $log,
                    $character,
                    $this->explorationRewardSnapshotWithEarnedCurrencies($beforeSnapshot, $character),
                    $this->context
                );
            }
        }

        Log::channel('reward_processing')->debug('processRewards: completed.', ['character_id' => $this->character->id]);

        if ($includeWinterEvent) {
            WinterEventChristmasGiftHandler::dispatch($this->character->id)->onConnection('event_battle_reward')->onQueue('event_battle_reward')->delay(now()->addSeconds(2));
        }
    }

    public function processLedgerAwareRewards(CharacterBattleRewardRequest $request, bool $includeWinterEvent = false): void
    {
        $payload = $request->handler_payload;

        $this->setUp($request->character_id, (int) $payload['monster_id']);
        $this->setContext($payload['context'] ?? []);

        if (is_null($this->character) || is_null($this->monster)) {
            return;
        }

        $this->battleRewardLedgerService->ensureSteps($request);
        $this->battleRewardMessageContext->start($request->id, $this->character->id, $this->character->user_id);

        try {
            foreach ($this->battleRewardLedgerService->stepsForRequest($request) as $step) {
                if ($step->status === BattleRewardStepStatus::COMPLETED) {
                    $this->battleRewardLedgerService->log('step.skipped_completed', $request, $step);

                    continue;
                }

                if (in_array($step->step_name, [BattleRewardStepName::FINAL_PLAYER_UPDATES, BattleRewardStepName::MESSAGE_OUTBOX], true)) {
                    break;
                }

                $this->battleRewardMessageContext->setStep($step->step_name);
                $startedAt = microtime(true);
                $step = $this->battleRewardLedgerService->startStep($step, $this->payloadForStep($request, $step));

                try {
                    $result = $this->runLedgerStep($request, $step, $includeWinterEvent);

                    if ($step->refresh()->status !== BattleRewardStepStatus::COMPLETED) {
                        $this->battleRewardLedgerService->completeStep($step, array_merge($result, [
                            'elapsed_ms' => (int) ((microtime(true) - $startedAt) * 1000),
                        ]));
                    }
                } catch (Throwable $throwable) {
                    $this->battleRewardLedgerService->failStep($step, $throwable);

                    throw $throwable;
                } finally {
                    $this->battleRewardMessageContext->clearStep();
                }
            }
        } finally {
            $this->battleRewardMessageContext->clear();
        }
    }

    private function runLedgerStep(
        CharacterBattleRewardRequest $request,
        CharacterBattleRewardRequestStep $step,
        bool $includeWinterEvent,
    ): array {
        match ($step->step_name) {
            BattleRewardStepName::BUILD_REWARD_PLAN => null,
            BattleRewardStepName::SKILL_POINTS => $this->handleAwardSkillPoints(),
            BattleRewardStepName::FACTION_POINTS => $this->handleFactionPoints(),
            BattleRewardStepName::FACTION_LOYALTY_BOUNTY => $this->handleFactionLoyaltyBounty(),
            BattleRewardStepName::CURRENCY_REWARDS => $this->handleLedgerCurrencyRewards($step),
            BattleRewardStepName::SPECIFIC_LOCATION_REWARDS => $this->handleLedgerSpecificLocationRewards($step),
            BattleRewardStepName::ITEM_DROPS => $this->handleLedgerItemDrops($step),
            BattleRewardStepName::WEEKLY_REWARDS => $this->handleWeeklyFightRewards(),
            BattleRewardStepName::SECONDARY_REWARDS => $this->handleSecondaryRewards(),
            BattleRewardStepName::GLOBAL_EVENT_PARTICIPATION => $this->handleGlobalEventParticipation(),
            BattleRewardStepName::XP => $this->handleLedgerAwardingXp($step),
            BattleRewardStepName::EXPLORATION_CONTEXT => $this->handleLedgerExplorationContext($request),
            BattleRewardStepName::WINTER_EVENT => $this->handleLedgerWinterEvent($includeWinterEvent),
            BattleRewardStepName::FINAL_PLAYER_UPDATES,
            BattleRewardStepName::MESSAGE_OUTBOX => null,
        };

        $this->character = $this->character?->refresh();

        return ['character_level' => $this->character?->level, 'character_xp' => $this->character?->xp];
    }

    private function payloadForStep(CharacterBattleRewardRequest $request, CharacterBattleRewardRequestStep $step): array
    {
        if ($step->step_name !== BattleRewardStepName::BUILD_REWARD_PLAN) {
            return $step->payload_json ?? [];
        }

        return [
            'character_id' => $this->character->id,
            'monster_id' => $this->monster->id,
            'source_type' => $request->source_type?->value,
            'source_id' => $request->source_id,
            'handler_payload' => $request->handler_payload,
            'context' => $this->context,
            'total_creatures' => $this->context['total_creatures'] ?? null,
            'total_xp' => $this->context['total_xp'] ?? null,
            'total_skill_xp' => $this->context['total_skill_xp'] ?? null,
            'total_faction_points' => $this->context['total_faction_points'] ?? null,
            'exploration_log_id' => $this->context['exploration_log_id'] ?? null,
            'starting' => $this->explorationRewardSnapshot(),
            'started_at' => now()->toIso8601String(),
        ];
    }

    private function handleLedgerAwardingXp(CharacterBattleRewardRequestStep $step): void
    {
        $payload = $step->payload_json ?? [];

        if (! isset($payload['total_xp'])) {
            $payload = array_merge($payload, [
                'total_xp' => $this->context['total_xp'] ?? $this->characterRewardService->setCharacter($this->character)->fetchXpForMonster($this->monster),
                'starting_level' => $this->character->level,
                'starting_xp' => $this->character->xp,
                'source_request_id' => $step->character_battle_reward_request_id,
                'source_type' => $step->request?->source_type?->value,
                'source_id' => $step->request?->source_id,
                'max_level_context' => [
                    'current_level' => $this->character->level,
                ],
                'planned_at' => now()->toIso8601String(),
            ]);

            $step = $this->battleRewardLedgerService->updateStepPayload($step, $payload);
        }

        $checkpoint = $step->checkpoint_json ?? [];
        $remainingXp = (int) ($checkpoint['remaining_xp'] ?? $payload['total_xp']);

        if (isset($this->context['total_xp'], $this->context['total_creatures']) && empty($checkpoint)) {
            $this->battleMessageHandler->handleMessageForExplorationXp(
                $this->character->user,
                $this->context['total_creatures'],
                $payload['total_xp'],
            );
        }

        DB::transaction(function () use ($step, $payload, $remainingXp): void {
            $this->characterRewardService
                ->setCharacter($this->character)
                ->distributeCheckpointedXp($remainingXp, function (int $appliedXp, int $levelsAwarded, Character $character) use ($step, $payload): void {
                    $this->battleRewardLedgerService->checkpointStep($step->refresh(), [
                        'applied_xp' => (int) $payload['total_xp'],
                        'levels_awarded' => $levelsAwarded,
                        'current_level' => $character->level,
                        'current_xp' => $character->xp,
                        'remaining_xp' => 0,
                        'last_checkpoint_at' => now()->toIso8601String(),
                    ]);
                });
        });

        $this->battleRewardLedgerService->checkpointStep($step->refresh(), [
            'applied_xp' => (int) $payload['total_xp'],
            'levels_awarded' => max(0, $this->character->refresh()->level - (int) $payload['starting_level']),
            'current_level' => $this->character->level,
            'current_xp' => $this->character->xp,
            'remaining_xp' => 0,
            'last_checkpoint_at' => now()->toIso8601String(),
        ]);
    }

    private function handleLedgerCurrencyRewards(CharacterBattleRewardRequestStep $step): void
    {
        $totalKills = isset($this->context['total_creatures']) ? $this->context['total_creatures'] : 1;
        $payload = $step->payload_json ?? [];

        if (! isset($payload['plan'])) {
            $payload['plan'] = $this->characterRewardService
                ->setCharacter($this->character)
                ->planCurrencies($this->monster, $totalKills);

            $payload['planned_at'] = now()->toIso8601String();
            $step = $this->battleRewardLedgerService->updateStepPayload($step, $payload);
        }

        $goldBeforeReward = $this->character->gold;

        DB::transaction(function () use ($step, $payload, $goldBeforeReward): void {
            $this->earnedCurrencies = $this->characterRewardService
                ->setCharacter($this->character)
                ->applyPlannedCurrencies($payload['plan']);

            $character = $this->character->refresh();
            $goldGained = $character->gold - $goldBeforeReward;

            $this->goldRush->processPotentialGoldRush($character, $goldGained);

            $this->character = $character->refresh();

            $this->battleRewardLedgerService->completeStep($step, [
                'applied' => true,
                'currencies' => $this->earnedCurrencies,
            ]);
        });
    }

    private function handleLedgerItemDrops(CharacterBattleRewardRequestStep $step): void
    {
        $totalKills = isset($this->context['total_creatures']) ? $this->context['total_creatures'] : 1;
        $payload = $step->payload_json ?? [];

        if (! isset($payload['plan'])) {
            $lootingChance = $this->character->skills->where('name', '=', 'Looting')->first()->skill_bonus;
            $payload['plan'] = $this->dropCheckService->planDrops($this->character, $this->monster, $totalKills, $lootingChance);
            $payload['planned_at'] = now()->toIso8601String();
            $step = $this->battleRewardLedgerService->updateStepPayload($step, $payload);
        }

        DB::transaction(function () use ($step, $payload): void {
            $this->addDropRewardTotals(
                $this->dropCheckService->applyPlannedDrops($this->character, $this->monster, $payload['plan'])
            );

            $this->character = $this->character->refresh();

            $this->battleRewardLedgerService->completeStep($step, [
                'applied' => true,
                'drop_count' => count($payload['plan']['drops'] ?? []),
            ]);
        });
    }

    private function handleLedgerSpecificLocationRewards(CharacterBattleRewardRequestStep $step): void
    {
        $payload = $step->payload_json ?? [];
        $totalKills = isset($this->context['total_creatures']) ? $this->context['total_creatures'] : 1;

        if (! isset($payload['plan'])) {
            $step = DB::transaction(function () use ($step, $payload, $totalKills): CharacterBattleRewardRequestStep {
                $payload['plan'] = $this->battleLocationRewardService
                    ->setContext($this->character, $this->monster)
                    ->planLocationReward($this->character, $this->monster, [
                        'request_id' => $step->character_battle_reward_request_id,
                        'kill_count' => $totalKills,
                    ]);

                $payload['planned_at'] = now()->toIso8601String();

                return $this->battleRewardLedgerService->updateStepPayload($step, $payload);
            });
        }

        $payload = $step->payload_json ?? [];

        DB::transaction(function () use ($step, $payload): void {
            $result = $this->battleLocationRewardService
                ->setContext($this->character, $this->monster)
                ->applyPlannedLocationReward($this->character, $payload['plan']);

            $this->character = $this->character->refresh();

            foreach (($result['currencies'] ?? []) as $currency => $amount) {
                if ($amount > 0) {
                    $this->earnedCurrencies[$currency] = ($this->earnedCurrencies[$currency] ?? 0) + $amount;
                }
            }

            $this->battleRewardLedgerService->completeStep($step, [
                'applied' => true,
                'handler' => $payload['plan']['handler'] ?? null,
                'currencies' => $result['currencies'] ?? [],
                'item_count' => $result['item_count'] ?? 0,
                'event_created' => $result['event_created'] ?? false,
                'noop' => $result['noop'] ?? false,
            ]);
        });
    }

    private function handleLedgerExplorationContext(CharacterBattleRewardRequest $request): void
    {
        if (! isset($this->context['exploration_log_id'])) {
            return;
        }

        $planStep = $request->steps()
            ->where('step_name', BattleRewardStepName::BUILD_REWARD_PLAN)
            ->first();

        $beforeSnapshot = $planStep?->payload_json['starting'] ?? null;

        if (is_null($beforeSnapshot)) {
            return;
        }

        $log = ExplorationLog::find($this->context['exploration_log_id']);

        if (is_null($log)) {
            Log::channel('reward_ledger')->debug('exploration_context.missing_log', [
                'character_id' => $request->character_id,
                'request_id' => $request->id,
                'step_name' => BattleRewardStepName::EXPLORATION_CONTEXT->value,
                'source_type' => $request->source_type?->value,
                'source_id' => $request->source_id,
            ]);

            return;
        }

        $character = $this->character->refresh();

        ExplorationLogService::applyRewardContext(
            $log,
            $character,
            $this->explorationRewardSnapshotWithEarnedCurrencies($beforeSnapshot, $character),
            $this->context
        );
    }

    private function handleLedgerWinterEvent(bool $includeWinterEvent): void
    {
        if (! $includeWinterEvent) {
            return;
        }

        WinterEventChristmasGiftHandler::dispatch($this->character->id)
            ->onConnection('event_battle_reward')
            ->onQueue('event_battle_reward')
            ->delay(now()->addSeconds(2));
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
