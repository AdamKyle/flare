<?php

namespace App\Game\Automation\Handlers;

use App\Flare\Models\Character;
use App\Flare\Models\FactionLoyaltyAutomation;
use App\Flare\Models\FactionLoyaltyNpc;
use App\Flare\Models\Monster;
use App\Flare\Services\CharacterRewardService;
use App\Game\Automation\Enums\AutomatedFightResultType;
use App\Game\Automation\Events\AutomationLogUpdate;
use App\Game\Automation\Loggers\FactionLoyaltyAutomationFightLogger;
use App\Game\Automation\Values\AutomatedFightResult;
use App\Game\Battle\Handlers\BattleEventHandler;
use App\Game\Battle\Services\MonsterFightService;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Skills\Services\SkillService;

class AutomatedBountyFightHandler
{
    private const MAX_ATTACK_ATTEMPTS = 100;

    private const MAX_TRAINING_KILLS = 50;

    private const MAX_STALLED_ATTEMPTS = 10;

    private Character $character;

    private FactionLoyaltyAutomation $factionLoyaltyAutomation;

    private FactionLoyaltyNpc $factionLoyaltyNpc;

    private array $task = [];

    private string $attackType;

    private FactionLoyaltyAutomationFightLogger $factionLoyaltyAutomationFightLogger;

    private int $batchKills = 0;

    private int $batchTrainingKills = 0;

    private int $batchBountyKills = 0;

    private int $batchTotalXp = 0;

    private int $batchTotalSkillXp = 0;

    private array $lastFightData = [];

    private bool $lastFightStalled = false;

    private int $stalledAttempt = 0;

    private ?array $warningNotice = null;

    /**
     * Create the automated bounty fight handler.
     *
     * @param MonsterFightService $monsterFightService
     * @param BattleEventHandler $battleEventHandler
     * @param CharacterRewardService $characterRewardService
     * @param SkillService $skillService
     * @param AutomatedFightResult $automatedFightResult
     */
    public function __construct(
        private readonly MonsterFightService $monsterFightService,
        private readonly BattleEventHandler $battleEventHandler,
        private readonly CharacterRewardService $characterRewardService,
        private readonly SkillService $skillService,
        private readonly AutomatedFightResult $automatedFightResult,
    ) {}

    /**
     * Set up the handler.
     *
     * @param Character $character
     * @param FactionLoyaltyAutomation $factionLoyaltyAutomation
     * @param FactionLoyaltyNpc $factionLoyaltyNpc
     * @param array $task
     * @param string $attackType
     * @param FactionLoyaltyAutomationFightLogger $factionLoyaltyAutomationFightLogger
     * @return AutomatedBountyFightHandler
     */
    public function setUp(
        Character $character,
        FactionLoyaltyAutomation $factionLoyaltyAutomation,
        FactionLoyaltyNpc $factionLoyaltyNpc,
        array $task,
        string $attackType,
        FactionLoyaltyAutomationFightLogger $factionLoyaltyAutomationFightLogger,
    ): AutomatedBountyFightHandler {
        $this->character = $character;
        $this->factionLoyaltyAutomation = $factionLoyaltyAutomation;
        $this->factionLoyaltyNpc = $factionLoyaltyNpc;
        $this->task = $task;
        $this->attackType = $attackType;
        $this->factionLoyaltyAutomationFightLogger = $factionLoyaltyAutomationFightLogger;
        $this->resetBatchTotals();
        $this->lastFightData = [];
        $this->lastFightStalled = false;
        $this->stalledAttempt = 0;
        $this->warningNotice = null;

        return $this;
    }

    /**
     * Handle automated bounty fighting.
     *
     * @return AutomatedFightResult
     */
    public function handle(): AutomatedFightResult
    {
        if (! $this->hasValidTask()) {
            return $this->finish(AutomatedFightResultType::INVALID_TASK, null, false, false, true);
        }

        $bountyMonster = Monster::find($this->task['monster_id']);

        if (is_null($bountyMonster)) {
            return $this->finish(AutomatedFightResultType::MONSTER_NOT_FOUND, null, false, false, true);
        }

        $remainingKills = $this->getRemainingBountyKills();

        if ($this->shouldRetryTrainingStalledFight()) {
            return $this->attemptRecoveryTraining($bountyMonster, false, true);
        }

        if ($remainingKills <= 0) {
            return $this->finish(AutomatedFightResultType::BOUNTY_COMPLETED, $bountyMonster, true);
        }

        $fightResultType = $this->fightBountyBatch($bountyMonster, $remainingKills);

        if ($this->batchBountyKills > 0) {
            $this->processBatchRewards($bountyMonster);
        }

        if ($fightResultType === AutomatedFightResultType::DIED_TO_BOUNTY_AFTER_TRAINING) {
            $this->sendOutEventLogUpdate('You died again after recovery training. Automation has ended. Check your gear child.', true);

            return $this->finish($fightResultType, $bountyMonster, true, false, true, true, true);
        }

        if ($fightResultType === AutomatedFightResultType::DIED_TO_BOUNTY_STARTED_TRAINING) {
            $this->setFailedBountyMonster($bountyMonster);

            $this->sendOutEventLogUpdate('You died to the bounty target. Automation revived you and started recovery training.', true);

            $this->finish($fightResultType, $bountyMonster, true, false, false, true);

            return $this->attemptRecoveryTraining($bountyMonster);
        }

        if ($fightResultType === AutomatedFightResultType::NOT_ENOUGH_HEALTH_OR_INVALID_STATE) {
            return $this->finish($fightResultType, $bountyMonster, true, false, true);
        }

        if (in_array($fightResultType, [
            AutomatedFightResultType::BOUNTY_STALLED_MAX_ATTEMPTS_REACHED,
            AutomatedFightResultType::TRAINING_STALLED_MAX_ATTEMPTS_REACHED,
        ], true)) {
            return $this->finish($fightResultType, $bountyMonster, true, false, true);
        }

        return $this->finish($fightResultType, $bountyMonster, true);
    }

    /**
     * Does the task have the required bounty fields?
     *
     * @return bool
     */
    private function hasValidTask(): bool
    {
        return isset($this->task['monster_id'], $this->task['required_amount'], $this->task['current_amount']);
    }

    /**
     * Get the remaining bounty kills.
     *
     * @return int
     */
    private function getRemainingBountyKills(): int
    {
        return max(0, $this->task['required_amount'] - $this->task['current_amount']);
    }

    /**
     * Fight the bounty batch.
     *
     * @param Monster $bountyMonster
     * @param int $remainingKills
     * @return AutomatedFightResultType
     */
    private function fightBountyBatch(Monster $bountyMonster, int $remainingKills): AutomatedFightResultType
    {
        while ($this->batchBountyKills < $remainingKills) {
            $fightData = $this->fightMonsterUntilResolved(
                $bountyMonster,
                $this->shouldRetryStalledFight($bountyMonster, true, false)
            );

            if (empty($fightData)) {
                return AutomatedFightResultType::NOT_ENOUGH_HEALTH_OR_INVALID_STATE;
            }

            if ($this->lastFightStalled) {
                return $this->prepareStalledResult($bountyMonster, true, false);
            }

            if ($this->hasCharacterDied($fightData)) {
                if ($this->hasCompletedTrainingForFailedBounty($bountyMonster)) {
                    return AutomatedFightResultType::DIED_TO_BOUNTY_AFTER_TRAINING;
                }

                return AutomatedFightResultType::DIED_TO_BOUNTY_STARTED_TRAINING;
            }

            if (! $this->hasMonsterDied($fightData)) {
                return AutomatedFightResultType::NOT_ENOUGH_HEALTH_OR_INVALID_STATE;
            }

            $this->batchKills++;
            $this->batchBountyKills++;
            $this->aggregateRewards($bountyMonster);
        }

        return AutomatedFightResultType::BOUNTY_COMPLETED;
    }

    /**
     * Attempt recovery training.
     *
     * @param Monster $failedBountyMonster
     * @param bool $reviveCharacter
     * @param bool $retryCachedFight
     * @return AutomatedFightResult
     */
    private function attemptRecoveryTraining(Monster $failedBountyMonster, bool $reviveCharacter = true, bool $retryCachedFight = false): AutomatedFightResult
    {
        if ($reviveCharacter) {
            $this->character = $this->battleEventHandler->processRevive($this->character->refresh());
        }

        $this->resetBatchTotals();

        $trainingMonster = $this->getTrainingMonster($failedBountyMonster);

        if (is_null($trainingMonster)) {
            $this->sendOutEventLogUpdate('No recovery training monster could be found. Automation has ended.', true);

            return $this->finish(AutomatedFightResultType::NO_TRAINING_MONSTER_FOUND, $failedBountyMonster, false, false, true);
        }

        $this->sendOutEventLogUpdate('Recovery training has started. Automation will fight up to 50 training monsters in this job run.', true);

        while ($this->batchTrainingKills < self::MAX_TRAINING_KILLS) {
            $fightData = $this->fightMonsterUntilResolved($trainingMonster, $retryCachedFight);
            $retryCachedFight = false;

            if (empty($fightData)) {
                return $this->finish(AutomatedFightResultType::NOT_ENOUGH_HEALTH_OR_INVALID_STATE, $trainingMonster, false, true, true);
            }

            if ($this->lastFightStalled) {
                $fightResultType = $this->prepareStalledResult($trainingMonster, false, true);

                return $this->finish(
                    $fightResultType,
                    $trainingMonster,
                    false,
                    true,
                    $this->stalledAttempt >= self::MAX_STALLED_ATTEMPTS
                );
            }

            if ($this->hasCharacterDied($fightData)) {
                $this->sendOutEventLogUpdate('You died during recovery training. Automation has ended.', true);

                return $this->finish(AutomatedFightResultType::DIED_DURING_TRAINING, $trainingMonster, false, true, true, true);
            }

            if (! $this->hasMonsterDied($fightData)) {
                return $this->finish(AutomatedFightResultType::NOT_ENOUGH_HEALTH_OR_INVALID_STATE, $trainingMonster, false, true, true);
            }

            $this->batchKills++;
            $this->batchTrainingKills++;
            $this->aggregateRewards($trainingMonster);
        }

        $this->processBatchRewards($trainingMonster);

        $this->sendOutEventLogUpdate('Recovery training completed. Automation will retry the failed bounty target on the next job run.', true);

        return $this->finish(AutomatedFightResultType::TRAINING_BATCH_COMPLETED, $trainingMonster, false, true, false, false, true);
    }

    /**
     * Fight one monster until the monster dies, the character dies, or the attack limit is reached.
     *
     * @param Monster $monster
     * @param bool $retryCachedFight
     * @return array
     */
    private function fightMonsterUntilResolved(Monster $monster, bool $retryCachedFight = false): array
    {
        $this->lastFightStalled = false;

        if ($retryCachedFight) {
            $fightData = $this->monsterFightService->fightMonster($this->character, $this->attackType, false, true);
        } else {
            $fightData = $this->monsterFightService->setupMonster($this->character, [
                'selected_monster_id' => $monster->id,
                'attack_type' => $this->attackType,
            ], true);
        }

        $this->lastFightData = $fightData;

        if (empty($fightData) || $this->hasCharacterDied($fightData) || $this->hasMonsterDied($fightData)) {
            return $fightData;
        }

        $attackAttempts = $retryCachedFight ? 1 : 0;

        while ($this->shouldAttackAgain($fightData) && $attackAttempts < self::MAX_ATTACK_ATTEMPTS) {
            $fightData = $this->monsterFightService->fightMonster($this->character, $this->attackType, false, true);
            $this->lastFightData = $fightData;
            $attackAttempts++;

            if (empty($fightData) || $this->hasCharacterDied($fightData) || $this->hasMonsterDied($fightData)) {
                return $fightData;
            }
        }

        if ($this->shouldAttackAgain($fightData)) {
            $this->lastFightStalled = true;

            return $fightData;
        }

        return $fightData;
    }

    /**
     * Should the monster be attacked again?
     *
     * @param array $fightData
     * @return bool
     */
    private function shouldAttackAgain(array $fightData): bool
    {
        if (! isset($fightData['health']['current_monster_health'])) {
            return false;
        }

        return $fightData['health']['current_monster_health'] > 0;
    }

    /**
     * Has the character died?
     *
     * @param array $fightData
     * @return bool
     */
    private function hasCharacterDied(array $fightData): bool
    {
        if (! isset($fightData['health']['current_character_health'])) {
            return false;
        }

        return $fightData['health']['current_character_health'] <= 0;
    }

    /**
     * Has the monster died?
     *
     * @param array $fightData
     * @return bool
     */
    private function hasMonsterDied(array $fightData): bool
    {
        if (! isset($fightData['health']['current_monster_health'])) {
            return false;
        }

        return $fightData['health']['current_monster_health'] <= 0;
    }

    /**
     * Get a recovery training monster.
     *
     * @param Monster $failedBountyMonster
     * @return Monster|null
     */
    private function getTrainingMonster(Monster $failedBountyMonster): ?Monster
    {
        return Monster::where('game_map_id', $failedBountyMonster->game_map_id)
            ->where('is_raid_monster', false)
            ->where('is_raid_boss', false)
            ->where('is_celestial_entity', false)
            ->whereNull('raid_special_attack_type')
            ->whereNull('only_for_location_type')
            ->where('max_level', '>', $this->character->level)
            ->where('max_level', '<', $failedBountyMonster->max_level)
            ->orderBy('max_level', 'asc')
            ->first();
    }

    /**
     * Has training completed for this failed bounty monster?
     *
     * @param Monster $bountyMonster
     * @return bool
     */
    private function hasCompletedTrainingForFailedBounty(Monster $bountyMonster): bool
    {
        return $this->factionLoyaltyAutomation->failed_bounty_monster_id === $bountyMonster->id &&
            $this->factionLoyaltyAutomation->trained_failed_bounty_monster_id === $bountyMonster->id;
    }

    /**
     * Should the cached training fight be retried?
     *
     * @return bool
     */
    private function shouldRetryTrainingStalledFight(): bool
    {
        return $this->factionLoyaltyAutomation->last_fight_was_training &&
            $this->factionLoyaltyAutomation->last_fight_outcome === AutomatedFightResultType::TRAINING_STALLED_RETRY->value &&
            $this->factionLoyaltyAutomation->last_fight_stalled_attempt < self::MAX_STALLED_ATTEMPTS;
    }

    /**
     * Should a stalled cached fight be retried?
     *
     * @param Monster $monster
     * @param bool $bountyTarget
     * @param bool $training
     * @return bool
     */
    private function shouldRetryStalledFight(Monster $monster, bool $bountyTarget, bool $training): bool
    {
        if (! $this->lastFightMatches($monster, $bountyTarget, $training)) {
            return false;
        }

        return in_array($this->factionLoyaltyAutomation->last_fight_outcome, [
            AutomatedFightResultType::BOUNTY_STALLED_RETRY->value,
            AutomatedFightResultType::TRAINING_STALLED_RETRY->value,
        ], true) && $this->factionLoyaltyAutomation->last_fight_stalled_attempt < self::MAX_STALLED_ATTEMPTS;
    }

    /**
     * Prepare the stalled fight result.
     *
     * @param Monster $monster
     * @param bool $bountyTarget
     * @param bool $training
     * @return AutomatedFightResultType
     */
    private function prepareStalledResult(Monster $monster, bool $bountyTarget, bool $training): AutomatedFightResultType
    {
        $this->stalledAttempt = $this->getStalledAttemptCount($monster, $bountyTarget, $training) + 1;
        $this->warningNotice = null;

        if ($this->stalledAttempt >= self::MAX_STALLED_ATTEMPTS) {
            $this->warningNotice = [
                'message' => $this->buildStalledWarningMessage($monster),
                'read' => false,
            ];

            event(new ServerMessageEvent($this->character->user, $this->warningNotice['message']));

            return $training
                ? AutomatedFightResultType::TRAINING_STALLED_MAX_ATTEMPTS_REACHED
                : AutomatedFightResultType::BOUNTY_STALLED_MAX_ATTEMPTS_REACHED;
        }

        return $training
            ? AutomatedFightResultType::TRAINING_STALLED_RETRY
            : AutomatedFightResultType::BOUNTY_STALLED_RETRY;
    }

    /**
     * Get the stalled attempt count for the same monster and phase.
     *
     * @param Monster $monster
     * @param bool $bountyTarget
     * @param bool $training
     * @return int
     */
    private function getStalledAttemptCount(Monster $monster, bool $bountyTarget, bool $training): int
    {
        if (! $this->lastFightMatches($monster, $bountyTarget, $training)) {
            return 0;
        }

        return $this->factionLoyaltyAutomation->last_fight_stalled_attempt;
    }

    /**
     * Does the last fight state match this monster and phase?
     *
     * @param Monster $monster
     * @param bool $bountyTarget
     * @param bool $training
     * @return bool
     */
    private function lastFightMatches(Monster $monster, bool $bountyTarget, bool $training): bool
    {
        return $this->factionLoyaltyAutomation->last_fight_monster_id === $monster->id &&
            $this->factionLoyaltyAutomation->last_fight_was_bounty_target === $bountyTarget &&
            $this->factionLoyaltyAutomation->last_fight_was_training === $training;
    }

    /**
     * Build the stalled warning message.
     *
     * @param Monster $monster
     * @return string
     */
    private function buildStalledWarningMessage(Monster $monster): string
    {
        return 'You tried to kill ' . $monster->name . ' 10 times and failed to do so. The NPC: ' . $this->factionLoyaltyNpc->npc->real_name . ', is now infuriated. Check your gear child. Go to Faction Loyalty.';
    }

    /**
     * Set the failed bounty monster.
     *
     * @param Monster $bountyMonster
     * @return void
     */
    private function setFailedBountyMonster(Monster $bountyMonster): void
    {
        $this->factionLoyaltyAutomation->update([
            'failed_bounty_monster_id' => $bountyMonster->id,
        ]);

        $this->factionLoyaltyAutomation = $this->factionLoyaltyAutomation->refresh();
    }

    /**
     * Aggregate rewards for one killed monster.
     *
     * @param Monster $monster
     * @return void
     */
    private function aggregateRewards(Monster $monster): void
    {
        $characterRewardService = $this->characterRewardService->setCharacter($this->character);
        $characterSkillService = $this->skillService->setSkillInTraining($this->character);

        $this->batchTotalXp += $characterRewardService->fetchXpForMonster($monster);
        $this->batchTotalSkillXp += $characterSkillService->getXpForSkillIntraining($this->character, $monster->xp);
    }

    /**
     * Process batch rewards once for the batch.
     *
     * @param Monster $monster
     * @return void
     */
    private function processBatchRewards(Monster $monster): void
    {
        if ($this->batchKills <= 0) {
            return;
        }

        $this->battleEventHandler->processMonsterDeath($this->character->id, $monster->id, [
            'total_creatures' => $this->batchKills,
            'total_xp' => $this->batchTotalXp,
            'total_faction_points' => 0,
            'total_skill_xp' => $this->batchTotalSkillXp,
            'skip_faction_loyalty_update_event' => true,
        ]);
    }

    /**
     * Log and return a fight result.
     *
     * @param AutomatedFightResultType $automatedFightResultType
     * @param Monster|null $monster
     * @param bool $bountyTarget
     * @param bool $training
     * @param bool $endedAutomation
     * @param bool $characterDied
     * @param bool $trainedForFailedBounty
     * @return AutomatedFightResult
     */
    private function finish(
        AutomatedFightResultType $automatedFightResultType,
        ?Monster $monster = null,
        bool $bountyTarget = false,
        bool $training = false,
        bool $endedAutomation = false,
        bool $characterDied = false,
        bool $trainedForFailedBounty = false,
    ): AutomatedFightResult {
        $automatedFightResult = (clone $this->automatedFightResult)
            ->setUp($automatedFightResultType)
            ->setMonsterId($monster?->id)
            ->setMonsterName($monster?->name)
            ->setBountyTarget($bountyTarget)
            ->setTraining($training)
            ->setFailedBountyMonsterId($this->factionLoyaltyAutomation->failed_bounty_monster_id)
            ->setTrainedForFailedBounty($trainedForFailedBounty)
            ->setKills($this->batchKills)
            ->setTrainingKills($this->batchTrainingKills)
            ->setBountyKills($this->batchBountyKills)
            ->setTotalCreatures($this->batchKills)
            ->setTotalXp($this->batchTotalXp)
            ->setTotalSkillXp($this->batchTotalSkillXp)
            ->setTotalFactionPoints(0)
            ->setCharacterDied($characterDied)
            ->setEndedAutomation($endedAutomation)
            ->setFightData($this->lastFightData)
            ->setStalledAttempt($this->stalledAttempt)
            ->setWarningNotice($this->warningNotice);

        $this->factionLoyaltyAutomationFightLogger->log($automatedFightResult);

        return $automatedFightResult;
    }

    /**
     * Reset the current batch totals.
     *
     * @return void
     */
    private function resetBatchTotals(): void
    {
        $this->batchKills = 0;
        $this->batchTrainingKills = 0;
        $this->batchBountyKills = 0;
        $this->batchTotalXp = 0;
        $this->batchTotalSkillXp = 0;
    }

    /**
     * Send the automation log update.
     *
     * @param string $message
     * @param bool $makeItalic
     * @param bool $isReward
     * @return void
     */
    private function sendOutEventLogUpdate(string $message, bool $makeItalic = false, bool $isReward = false): void
    {
        if ($this->character->isLoggedIn()) {
            event(new AutomationLogUpdate($this->character->user->id, $message, $makeItalic, $isReward));
        }
    }
}
