<?php

namespace App\Game\Automation\Jobs;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterAutomation;
use App\Flare\Models\FactionLoyaltyAutomation;
use App\Flare\Models\FactionLoyaltyAutomationWarning;
use App\Flare\Models\FactionLoyaltyNpc;
use App\Game\Automation\Coordinators\FactionLoyaltyAutomationActionCoordinator;
use App\Game\Automation\Coordinators\FactionLoyaltyNpcTaskCoordinator;
use App\Game\Automation\Enums\AutomatedCraftingResultType;
use App\Game\Automation\Enums\AutomatedFightResultType;
use App\Game\Automation\Enums\FactionLoyaltyCoordinatorAction;
use App\Game\Automation\Events\AutomationLogUpdate;
use App\Game\Automation\Events\AutomationStatus;
use App\Game\Automation\Events\AutomationTimeOut;
use App\Game\Automation\Handlers\AutomatedBountyFightHandler;
use App\Game\Automation\Handlers\AutomatedCraftingHandler;
use App\Game\Automation\Loggers\FactionLoyaltyAutomationCraftingLogger;
use App\Game\Automation\Loggers\FactionLoyaltyAutomationFightLogger;
use App\Game\Automation\Values\AutomatedCraftingResult;
use App\Game\Automation\Values\AutomatedFightResult;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Character\Builders\AttackBuilders\CharacterCacheData;
use App\Game\Core\Traits\SafelyBroadcastsEvents;
use App\Game\Factions\FactionLoyalty\Events\FactionLoyaltyAutomationWarningState;
use Carbon\Carbon;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AutomatedFactionLoyalty implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SafelyBroadcastsEvents, SerializesModels;

    public int $characterId;

    public int $automationId;

    public int $factionLoyaltyAutomationId;

    public int $timeDelay;

    private ?Character $character = null;

    private ?CharacterAutomation $characterAutomation = null;

    private ?Carbon $roundStartedAt = null;

    private ?FactionLoyaltyAutomation $factionLoyaltyAutomation = null;

    private ?FactionLoyaltyNpc $factionLoyaltyNpc = null;

    /**
     * Set up the job.
     *
     * @param int $characterId
     * @param int $automationId
     * @param int $factionLoyaltyAutomationId
     * @param int $timeDelay
     */
    public function __construct(int $characterId, int $automationId, int $factionLoyaltyAutomationId, int $timeDelay)
    {
        $this->characterId = $characterId;
        $this->automationId = $automationId;
        $this->factionLoyaltyAutomationId = $factionLoyaltyAutomationId;
        $this->timeDelay = $timeDelay;
    }

    /**
     * Handle the automated faction loyalty job.
     *
     * @param CharacterCacheData $characterCacheData
     * @param FactionLoyaltyNpcTaskCoordinator $factionLoyaltyNpcTaskCoordinator
     * @param FactionLoyaltyAutomationActionCoordinator $factionLoyaltyAutomationActionCoordinator
     * @param AutomatedCraftingHandler $automatedCraftingHandler
     * @param FactionLoyaltyAutomationCraftingLogger $factionLoyaltyAutomationCraftingLogger
     * @param AutomatedBountyFightHandler $automatedBountyFightHandler
     * @param FactionLoyaltyAutomationFightLogger $factionLoyaltyAutomationFightLogger
     * @return void
     */
    public function handle(
        CharacterCacheData $characterCacheData,
        FactionLoyaltyNpcTaskCoordinator $factionLoyaltyNpcTaskCoordinator,
        FactionLoyaltyAutomationActionCoordinator $factionLoyaltyAutomationActionCoordinator,
        AutomatedCraftingHandler $automatedCraftingHandler,
        FactionLoyaltyAutomationCraftingLogger $factionLoyaltyAutomationCraftingLogger,
        AutomatedBountyFightHandler $automatedBountyFightHandler,
        FactionLoyaltyAutomationFightLogger $factionLoyaltyAutomationFightLogger,
    ): void {
        Log::channel('faction_loyalty')->info('Faction loyalty job picked up.', [
            'character_id' => $this->characterId,
            'automation_id' => $this->automationId,
            'faction_loyalty_automation_id' => $this->factionLoyaltyAutomationId,
            'connection' => $this->connection,
            'queue' => $this->queue,
        ]);

        $this->character = Character::find($this->characterId);

        $this->characterAutomation = CharacterAutomation::where('character_id', $this->characterId)
            ->where('id', $this->automationId)
            ->first();

        $this->factionLoyaltyAutomation = FactionLoyaltyAutomation::where('character_id', $this->characterId)
            ->where('id', $this->factionLoyaltyAutomationId)
            ->first();

        Log::channel('faction_loyalty')->info('Faction loyalty job records loaded.', [
            'character_id' => $this->characterId,
            'character_name' => $this->character?->name,
            'automation_id' => $this->automationId,
            'faction_loyalty_automation_id' => $this->factionLoyaltyAutomationId,
            'character_found' => ! is_null($this->character),
            'character_automation_found' => ! is_null($this->characterAutomation),
            'faction_loyalty_automation_found' => ! is_null($this->factionLoyaltyAutomation),
        ]);

        Log::info('Faction loyalty automation job running.', [
            'character_id' => $this->characterId,
            'automation_id' => $this->automationId,
            'faction_loyalty_automation_id' => $this->factionLoyaltyAutomationId,
        ]);

        if ($this->shouldBail()) {
            $this->endAutomation($characterCacheData);

            return;
        }

        $this->roundStartedAt = now();

        try {
            Log::channel('faction_loyalty')->info('Faction loyalty resolving NPC.', [
                'character_id' => $this->character->id,
                'character_name' => $this->character->name,
                'automation_id' => $this->characterAutomation->id,
                'faction_loyalty_automation_id' => $this->factionLoyaltyAutomation->id,
            ]);

            $this->factionLoyaltyNpc = $this->resolveFactionLoyaltyNpc($factionLoyaltyNpcTaskCoordinator);

            if (is_null($this->factionLoyaltyNpc) || $factionLoyaltyNpcTaskCoordinator->shouldEndAutomation()) {
                Log::channel('faction_loyalty')->warning('Faction loyalty NPC could not be resolved. Automation ending.', [
                    'character_id' => $this->character->id,
                    'character_name' => $this->character->name,
                    'automation_id' => $this->characterAutomation->id,
                    'faction_loyalty_automation_id' => $this->factionLoyaltyAutomation->id,
                ]);

                $this->endAutomation($characterCacheData, false);

                return;
            }

            Log::channel('faction_loyalty')->info('Faction loyalty NPC resolved.', [
                'character_id' => $this->character->id,
                'character_name' => $this->character->name,
                'automation_id' => $this->characterAutomation->id,
                'faction_loyalty_automation_id' => $this->factionLoyaltyAutomation->id,
                'faction_loyalty_npc_id' => $this->factionLoyaltyNpc->id,
                'npc_name' => $this->factionLoyaltyNpc->npc?->real_name,
            ]);

            Log::channel('faction_loyalty')->info('Faction loyalty resolving next action.', [
                'character_id' => $this->character->id,
                'character_name' => $this->character->name,
                'automation_id' => $this->characterAutomation->id,
                'faction_loyalty_automation_id' => $this->factionLoyaltyAutomation->id,
                'faction_loyalty_npc_id' => $this->factionLoyaltyNpc->id,
                'npc_name' => $this->factionLoyaltyNpc->npc?->real_name,
            ]);

            $factionLoyaltyAutomationAction = $this->resolveFactionLoyaltyAutomationAction($factionLoyaltyAutomationActionCoordinator);

            if (is_null($factionLoyaltyAutomationAction)) {
                Log::channel('faction_loyalty')->warning('Faction loyalty action could not be resolved. Automation ending.', [
                    'character_id' => $this->character->id,
                    'character_name' => $this->character->name,
                    'automation_id' => $this->characterAutomation->id,
                    'faction_loyalty_automation_id' => $this->factionLoyaltyAutomation->id,
                    'faction_loyalty_npc_id' => $this->factionLoyaltyNpc->id,
                    'npc_name' => $this->factionLoyaltyNpc->npc?->real_name,
                ]);

                $this->sendOutEventLogUpdate(
                    'No faction loyalty automation action could be resolved. Automation canceled.',
                    true
                );

                $this->endAutomation($characterCacheData, false);

                return;
            }

            Log::channel('faction_loyalty')->info('Faction loyalty action resolved.', [
                'character_id' => $this->character->id,
                'character_name' => $this->character->name,
                'automation_id' => $this->characterAutomation->id,
                'faction_loyalty_automation_id' => $this->factionLoyaltyAutomation->id,
                'faction_loyalty_npc_id' => $this->factionLoyaltyNpc->id,
                'npc_name' => $this->factionLoyaltyNpc->npc?->real_name,
                'action_type' => $factionLoyaltyAutomationAction['type'] ?? null,
                'item_id' => $factionLoyaltyAutomationAction['task']['item_id'] ?? null,
                'monster_id' => $factionLoyaltyAutomationAction['task']['monster_id'] ?? null,
            ]);

            $this->handleFactionLoyaltyAutomationAction(
                $factionLoyaltyAutomationAction,
                $automatedCraftingHandler,
                $factionLoyaltyAutomationCraftingLogger,
                $automatedBountyFightHandler,
                $factionLoyaltyAutomationFightLogger,
                $characterCacheData
            );
        } catch (Exception $exception) {
            $this->handleAutomationException($exception, $characterCacheData);

            return;
        }
    }

    /**
     * Resolve the faction loyalty NPC.
     *
     * @param FactionLoyaltyNpcTaskCoordinator $factionLoyaltyNpcTaskCoordinator
     * @return FactionLoyaltyNpc|null
     * @throws Exception
     */
    private function resolveFactionLoyaltyNpc(FactionLoyaltyNpcTaskCoordinator $factionLoyaltyNpcTaskCoordinator): ?FactionLoyaltyNpc
    {
        return $factionLoyaltyNpcTaskCoordinator
            ->setUp($this->character, $this->factionLoyaltyAutomation)
            ->resolveNpc();
    }

    /**
     * Resolve the faction loyalty automation action.
     *
     * @param FactionLoyaltyAutomationActionCoordinator $factionLoyaltyAutomationActionCoordinator
     * @return array|null
     */
    private function resolveFactionLoyaltyAutomationAction(FactionLoyaltyAutomationActionCoordinator $factionLoyaltyAutomationActionCoordinator): ?array
    {
        return $factionLoyaltyAutomationActionCoordinator
            ->setUp($this->factionLoyaltyAutomation, $this->factionLoyaltyNpc)
            ->resolveAction();
    }

    /**
     * Handle the resolved automation action.
     *
     * @param array $factionLoyaltyAutomationAction
     * @param AutomatedCraftingHandler $automatedCraftingHandler
     * @param FactionLoyaltyAutomationCraftingLogger $factionLoyaltyAutomationCraftingLogger
     * @param AutomatedBountyFightHandler $automatedBountyFightHandler
     * @param FactionLoyaltyAutomationFightLogger $factionLoyaltyAutomationFightLogger
     * @param CharacterCacheData $characterCacheData
     * @return void
     */
    private function handleFactionLoyaltyAutomationAction(
        array $factionLoyaltyAutomationAction,
        AutomatedCraftingHandler $automatedCraftingHandler,
        FactionLoyaltyAutomationCraftingLogger $factionLoyaltyAutomationCraftingLogger,
        AutomatedBountyFightHandler $automatedBountyFightHandler,
        FactionLoyaltyAutomationFightLogger $factionLoyaltyAutomationFightLogger,
        CharacterCacheData $characterCacheData,
    ): void {
        if ($factionLoyaltyAutomationAction['type'] === FactionLoyaltyCoordinatorAction::CRAFT->value) {
            $this->handleCraftingAction(
                $factionLoyaltyAutomationAction,
                $automatedCraftingHandler,
                $factionLoyaltyAutomationCraftingLogger,
                $automatedBountyFightHandler,
                $factionLoyaltyAutomationFightLogger,
                $characterCacheData
            );

            return;
        }

        if ($factionLoyaltyAutomationAction['type'] === FactionLoyaltyCoordinatorAction::FIGHT->value) {
            $this->handleFightAction(
                $factionLoyaltyAutomationAction,
                $automatedBountyFightHandler,
                $factionLoyaltyAutomationFightLogger,
                $characterCacheData
            );

            return;
        }

        $this->sendOutEventLogUpdate('Unknown faction loyalty automation action. Automation canceled.', true);

        $this->endAutomation($characterCacheData, false);
    }

    /**
     * Handle the crafting action.
     *
     * @param array $factionLoyaltyAutomationAction
     * @param AutomatedCraftingHandler $automatedCraftingHandler
     * @param FactionLoyaltyAutomationCraftingLogger $factionLoyaltyAutomationCraftingLogger
     * @param AutomatedBountyFightHandler $automatedBountyFightHandler
     * @param FactionLoyaltyAutomationFightLogger $factionLoyaltyAutomationFightLogger
     * @param CharacterCacheData $characterCacheData
     * @return void
     */
    private function handleCraftingAction(
        array $factionLoyaltyAutomationAction,
        AutomatedCraftingHandler $automatedCraftingHandler,
        FactionLoyaltyAutomationCraftingLogger $factionLoyaltyAutomationCraftingLogger,
        AutomatedBountyFightHandler $automatedBountyFightHandler,
        FactionLoyaltyAutomationFightLogger $factionLoyaltyAutomationFightLogger,
        CharacterCacheData $characterCacheData,
    ): void {
        $task = $factionLoyaltyAutomationAction['task'] ?? [];

        if (! isset($task['item_id'])) {
            $this->sendOutEventLogUpdate('Faction loyalty crafting task is missing an item id. Automation canceled.', true);

            $this->endAutomation($characterCacheData, false);

            return;
        }

        Log::channel('faction_loyalty')->info('Faction loyalty crafting action started.', [
            'character_id' => $this->character->id,
            'character_name' => $this->character->name,
            'automation_id' => $this->characterAutomation->id,
            'faction_loyalty_automation_id' => $this->factionLoyaltyAutomation->id,
            'faction_loyalty_npc_id' => $this->factionLoyaltyNpc->id,
            'npc_name' => $this->factionLoyaltyNpc->npc?->real_name,
            'action_type' => FactionLoyaltyCoordinatorAction::CRAFT->value,
            'item_id' => $task['item_id'],
        ]);

        $automatedCraftingResult = $automatedCraftingHandler
            ->setUp(
                $this->character,
                $task['item_id'],
                $factionLoyaltyAutomationCraftingLogger->setUp($this->factionLoyaltyAutomation)
            )
            ->setCraftForNpc()
            ->setFactionLoyaltyNpc($this->factionLoyaltyNpc)
            ->handle();

        Log::channel('faction_loyalty')->info('Faction loyalty crafting action completed.', [
            'character_id' => $this->character->id,
            'character_name' => $this->character->name,
            'automation_id' => $this->characterAutomation->id,
            'faction_loyalty_automation_id' => $this->factionLoyaltyAutomation->id,
            'faction_loyalty_npc_id' => $this->factionLoyaltyNpc->id,
            'npc_name' => $this->factionLoyaltyNpc->npc?->real_name,
            'action_type' => FactionLoyaltyCoordinatorAction::CRAFT->value,
            'result_type' => $automatedCraftingResult->getResultType()->value,
            'item_id' => $automatedCraftingResult->getTargetItemId(),
        ]);

        $this->handleCraftingResult(
            $automatedCraftingResult,
            $automatedBountyFightHandler,
            $factionLoyaltyAutomationFightLogger,
            $characterCacheData
        );
    }

    /**
     * Handle the automated crafting result.
     *
     * @param AutomatedCraftingResult $automatedCraftingResult
     * @param AutomatedBountyFightHandler $automatedBountyFightHandler
     * @param FactionLoyaltyAutomationFightLogger $factionLoyaltyAutomationFightLogger
     * @param CharacterCacheData $characterCacheData
     * @return void
     */
    private function handleCraftingResult(
        AutomatedCraftingResult $automatedCraftingResult,
        AutomatedBountyFightHandler $automatedBountyFightHandler,
        FactionLoyaltyAutomationFightLogger $factionLoyaltyAutomationFightLogger,
        CharacterCacheData $characterCacheData,
    ): void {
        if ($automatedCraftingResult->hasStartedBelowTargetLevel()) {
            $this->setFailedCraftingItem($automatedCraftingResult->getTargetItemId());
        }

        if ($automatedCraftingResult->hasCraftedTargetItem()) {
            $this->clearFailedCraftingItem();
        }

        if ($automatedCraftingResult->getResultType() === AutomatedCraftingResultType::CRAFTED_TARGET_ITEM) {
            $this->recallJob($characterCacheData);

            return;
        }

        if ($automatedCraftingResult->getResultType() === AutomatedCraftingResultType::CRAFTED_TRAINING_ITEM) {
            $this->recallJob($characterCacheData);

            return;
        }

        if ($automatedCraftingResult->getResultType() === AutomatedCraftingResultType::MAX_ATTEMPTS_REACHED) {
            $this->recallJob($characterCacheData);

            return;
        }

        if ($automatedCraftingResult->getResultType() === AutomatedCraftingResultType::NOT_ENOUGH_GOLD) {
            $this->sendOutEventLogUpdate(
                'You do not have enough gold to continue crafting. Automation will switch to bounty fighting.',
                true
            );

            $factionLoyaltyAutomationAction = $this->getBountyFightActionFromCurrentNpc();

            if (is_null($factionLoyaltyAutomationAction)) {
                $factionLoyaltyAutomationLog = $this->factionLoyaltyAutomation->refresh()->log;

                FactionLoyaltyAutomationWarning::create([
                    'character_id' => $this->character->id,
                    'faction_loyalty_automation_id' => $this->factionLoyaltyAutomation->id,
                    'faction_loyalty_automation_log_id' => $factionLoyaltyAutomationLog?->id,
                    'faction_loyalty_npc_id' => $this->factionLoyaltyNpc->id,
                    'log_type' => 'crafting_logs',
                    'log_entry_id' => $automatedCraftingResult->getLogEntryId(),
                    'type' => AutomatedCraftingResultType::NOT_ENOUGH_GOLD->value,
                    'message' => 'Not enough gold to craft and no bounty remains for this NPC. Automation has ended.',
                ]);
                $this->dispatchWarningState();

                $this->sendOutEventLogUpdate(
                    'Not enough gold to craft and no bounty remains for this NPC. Automation has ended.',
                    true
                );

                $this->endAutomation($characterCacheData, false);

                return;
            }

            $this->handleFightAction(
                $factionLoyaltyAutomationAction,
                $automatedBountyFightHandler,
                $factionLoyaltyAutomationFightLogger,
                $characterCacheData
            );

            return;
        }

        $this->sendOutEventLogUpdate('Automated crafting could not continue. Automation canceled.', true);

        $this->endAutomation($characterCacheData, false);
    }

    /**
     * Get a bounty fight action from the current NPC.
     *
     * @return array|null
     */
    private function getBountyFightActionFromCurrentNpc(): ?array
    {
        $this->factionLoyaltyNpc = $this->factionLoyaltyNpc->refresh();

        $fameTasks = $this->factionLoyaltyNpc->factionLoyaltyNpcTasks?->fame_tasks ?? [];

        foreach ($fameTasks as $fameTask) {
            if (($fameTask['type'] ?? null) !== 'bounty') {
                continue;
            }

            if ($fameTask['current_amount'] >= $fameTask['required_amount']) {
                continue;
            }

            if (! is_null($this->factionLoyaltyAutomation->failed_bounty_monster_id) && $fameTask['monster_id'] !== $this->factionLoyaltyAutomation->failed_bounty_monster_id) {
                continue;
            }

            return [
                'type' => FactionLoyaltyCoordinatorAction::FIGHT->value,
                'task' => $fameTask,
            ];
        }

        foreach ($fameTasks as $fameTask) {
            if (($fameTask['type'] ?? null) !== 'bounty') {
                continue;
            }

            if ($fameTask['current_amount'] >= $fameTask['required_amount']) {
                continue;
            }

            return [
                'type' => FactionLoyaltyCoordinatorAction::FIGHT->value,
                'task' => $fameTask,
            ];
        }

        return null;
    }

    /**
     * Set the failed crafting item.
     *
     * @param int $itemId
     * @return void
     */
    private function setFailedCraftingItem(int $itemId): void
    {
        $this->factionLoyaltyAutomation->update([
            'failed_crafting_item_id' => $itemId,
        ]);

        $this->factionLoyaltyAutomation = $this->factionLoyaltyAutomation->refresh();
    }

    /**
     * Handle the fight action.
     *
     * @param array $factionLoyaltyAutomationAction
     * @param AutomatedBountyFightHandler $automatedBountyFightHandler
     * @param FactionLoyaltyAutomationFightLogger $factionLoyaltyAutomationFightLogger
     * @param CharacterCacheData $characterCacheData
     * @return void
     */
    private function handleFightAction(
        array $factionLoyaltyAutomationAction,
        AutomatedBountyFightHandler $automatedBountyFightHandler,
        FactionLoyaltyAutomationFightLogger $factionLoyaltyAutomationFightLogger,
        CharacterCacheData $characterCacheData,
    ): void {
        $task = $factionLoyaltyAutomationAction['task'] ?? [];

        if (! isset($task['monster_id'])) {
            $this->sendOutEventLogUpdate('Faction loyalty bounty task is missing a monster id. Automation canceled.', true);

            $this->endAutomation($characterCacheData, false);

            return;
        }

        Log::channel('faction_loyalty')->info('Faction loyalty bounty action started.', [
            'character_id' => $this->character->id,
            'character_name' => $this->character->name,
            'automation_id' => $this->characterAutomation->id,
            'faction_loyalty_automation_id' => $this->factionLoyaltyAutomation->id,
            'faction_loyalty_npc_id' => $this->factionLoyaltyNpc->id,
            'npc_name' => $this->factionLoyaltyNpc->npc?->real_name,
            'action_type' => FactionLoyaltyCoordinatorAction::FIGHT->value,
            'monster_id' => $task['monster_id'],
            'attack_type' => $this->characterAutomation->attack_type,
        ]);

        $automatedFightResult = $automatedBountyFightHandler
            ->setUp(
                $this->character,
                $this->factionLoyaltyAutomation,
                $this->factionLoyaltyNpc,
                $task,
                $this->characterAutomation->attack_type,
                $factionLoyaltyAutomationFightLogger->setUp($this->factionLoyaltyAutomation)
            )
            ->handle();

        Log::channel('faction_loyalty')->info('Faction loyalty bounty action completed.', [
            'character_id' => $this->character->id,
            'character_name' => $this->character->name,
            'automation_id' => $this->characterAutomation->id,
            'faction_loyalty_automation_id' => $this->factionLoyaltyAutomation->id,
            'faction_loyalty_npc_id' => $this->factionLoyaltyNpc->id,
            'npc_name' => $this->factionLoyaltyNpc->npc?->real_name,
            'action_type' => FactionLoyaltyCoordinatorAction::FIGHT->value,
            'result_type' => $automatedFightResult->getResultType()->value,
            'monster_id' => $automatedFightResult->getMonsterId(),
            'bounty_kills' => $automatedFightResult->getBountyKills(),
            'attack_type' => $this->characterAutomation->attack_type,
        ]);

        $this->handleFightResult($automatedFightResult, $characterCacheData);
    }

    /**
     * Handle the automated fight result.
     *
     * @param AutomatedFightResult $automatedFightResult
     * @param CharacterCacheData $characterCacheData
     * @return void
     */
    private function handleFightResult(AutomatedFightResult $automatedFightResult, CharacterCacheData $characterCacheData): void
    {
        if ($this->shouldClearFailedBountyMonster($automatedFightResult)) {
            $this->clearFailedBountyMonster();
        }

        if ($automatedFightResult->getResultType() === AutomatedFightResultType::BOUNTY_COMPLETED) {
            $this->recallJob($characterCacheData);

            return;
        }

        if ($automatedFightResult->getResultType() === AutomatedFightResultType::TRAINING_BATCH_COMPLETED) {
            $this->recallJob($characterCacheData);

            return;
        }

        if ($automatedFightResult->getResultType() === AutomatedFightResultType::DIED_TO_BOUNTY_STARTED_TRAINING) {
            $this->recallJob($characterCacheData);

            return;
        }

        if (in_array($automatedFightResult->getResultType(), [
            AutomatedFightResultType::BOUNTY_STALLED_RETRY,
            AutomatedFightResultType::TRAINING_STALLED_RETRY,
        ], true)) {
            $this->recallJob($characterCacheData);

            return;
        }

        if (in_array($automatedFightResult->getResultType(), [
            AutomatedFightResultType::NO_TRAINING_MONSTER_FOUND,
            AutomatedFightResultType::DIED_DURING_TRAINING,
            AutomatedFightResultType::DIED_TO_BOUNTY_AFTER_TRAINING,
        ], true)) {
            $warningMessage = 'You died fighting the bounty after recovery training. Automation has ended.';

            if ($automatedFightResult->getResultType() === AutomatedFightResultType::NO_TRAINING_MONSTER_FOUND) {
                $warningMessage = 'No recovery monster found. Automation has ended.';
            }

            if ($automatedFightResult->getResultType() === AutomatedFightResultType::DIED_DURING_TRAINING) {
                $warningMessage = 'You died during recovery training. Automation has ended.';
            }

            $factionLoyaltyAutomationLog = $this->factionLoyaltyAutomation->refresh()->log;

            FactionLoyaltyAutomationWarning::create([
                'character_id' => $this->character->id,
                'faction_loyalty_automation_id' => $this->factionLoyaltyAutomation->id,
                'faction_loyalty_automation_log_id' => $factionLoyaltyAutomationLog?->id,
                'faction_loyalty_npc_id' => $this->factionLoyaltyNpc?->id,
                'log_type' => 'fight_logs',
                'log_entry_id' => $automatedFightResult->getLogEntryId(),
                'type' => $automatedFightResult->getResultType()->value,
                'message' => $warningMessage,
            ]);
            $this->dispatchWarningState();
        }

        $this->endAutomation($characterCacheData, false);
    }

    /**
     * Dispatch faction loyalty automation warning state.
     *
     * @return void
     */
    private function dispatchWarningState(): void
    {
        $warningNotices = FactionLoyaltyAutomationWarning::where('character_id', $this->character->id)
            ->orderByDesc('id')
            ->get()
            ->map(function (FactionLoyaltyAutomationWarning $warning): array {
                return [
                    'id' => $warning->id,
                    'type' => $warning->type,
                    'message' => $warning->message,
                ];
            })
            ->values()
            ->toArray();

        $this->safelyDispatchBroadcastEvent(
            new FactionLoyaltyAutomationWarningState(
                $this->character->user,
                count($warningNotices) > 0,
                $warningNotices
            ),
            ['character_id' => $this->characterId, 'automation_id' => $this->automationId]
        );
    }

    /**
     * Should the failed bounty monster be cleared?
     *
     * @param AutomatedFightResult $automatedFightResult
     * @return bool
     */
    private function shouldClearFailedBountyMonster(AutomatedFightResult $automatedFightResult): bool
    {
        if (is_null($this->factionLoyaltyAutomation->failed_bounty_monster_id)) {
            return false;
        }

        if ($automatedFightResult->getBountyKills() <= 0) {
            return false;
        }

        return $automatedFightResult->getMonsterId() === $this->factionLoyaltyAutomation->failed_bounty_monster_id;
    }

    /**
     * Clear the failed bounty monster.
     *
     * @return void
     */
    private function clearFailedBountyMonster(): void
    {
        $this->factionLoyaltyAutomation->update([
            'failed_bounty_monster_id' => null,
            'trained_failed_bounty_monster_id' => null,
        ]);

        $this->factionLoyaltyAutomation = $this->factionLoyaltyAutomation->refresh();
    }

    /**
     * Clear the failed crafting item.
     *
     * @return void
     */
    private function clearFailedCraftingItem(): void
    {
        $this->factionLoyaltyAutomation->update([
            'failed_crafting_item_id' => null,
        ]);

        $this->factionLoyaltyAutomation = $this->factionLoyaltyAutomation->refresh();
    }

    /**
     * Recall the job.
     *
     * @param CharacterCacheData $characterCacheData
     * @return void
     */
    private function recallJob(CharacterCacheData $characterCacheData): void
    {
        if (now()->greaterThanOrEqualTo($this->characterAutomation->completed_at)) {
            Log::channel('faction_loyalty')->info('Faction loyalty job reached automation completion time.', [
                'character_id' => $this->character->id,
                'character_name' => $this->character->name,
                'automation_id' => $this->automationId,
                'faction_loyalty_automation_id' => $this->factionLoyaltyAutomationId,
                'completed_at' => $this->characterAutomation->completed_at,
            ]);

            $this->endAutomation($characterCacheData);

            return;
        }

        $delaySeconds = max(0, ($this->timeDelay * 60) - $this->roundStartedAt->diffInSeconds(now()));

        Log::channel('faction_loyalty')->info('Faction loyalty job recalled.', [
            'character_id' => $this->character->id,
            'character_name' => $this->character->name,
            'automation_id' => $this->automationId,
            'faction_loyalty_automation_id' => $this->factionLoyaltyAutomationId,
            'connection' => 'long_running',
            'queue' => 'faction_loyalty',
            'delay_seconds' => $delaySeconds,
        ]);

        AutomatedFactionLoyalty::dispatch(
            $this->characterId,
            $this->automationId,
            $this->factionLoyaltyAutomationId,
            $this->timeDelay
        )->delay(now()->addSeconds($delaySeconds))->onConnection('long_running')->onQueue('faction_loyalty');
    }

    /**
     * Should the job bail?
     *
     * @return bool
     */
    private function shouldBail(): bool
    {
        if (is_null($this->character)) {
            Log::channel('faction_loyalty')->warning('Faction loyalty job bailing: character not found.', [
                'character_id' => $this->characterId,
                'automation_id' => $this->automationId,
                'faction_loyalty_automation_id' => $this->factionLoyaltyAutomationId,
            ]);

            Log::warning('Faction loyalty automation bailing: character not found.', [
                'character_id' => $this->characterId,
                'automation_id' => $this->automationId,
            ]);

            return true;
        }

        if (is_null($this->characterAutomation) || is_null($this->factionLoyaltyAutomation)) {
            Log::channel('faction_loyalty')->warning('Faction loyalty job bailing: automation record not found.', [
                'character_id' => $this->characterId,
                'character_name' => $this->character->name,
                'automation_id' => $this->automationId,
                'faction_loyalty_automation_id' => $this->factionLoyaltyAutomationId,
            ]);

            Log::warning('Faction loyalty automation bailing: automation record not found.', [
                'character_id' => $this->characterId,
                'automation_id' => $this->automationId,
                'faction_loyalty_automation_id' => $this->factionLoyaltyAutomationId,
            ]);

            return true;
        }

        if (! is_null($this->factionLoyaltyAutomation->completed_at)) {
            Log::channel('faction_loyalty')->warning('Faction loyalty job bailing: faction loyalty automation already completed.', [
                'character_id' => $this->characterId,
                'character_name' => $this->character->name,
                'automation_id' => $this->automationId,
                'faction_loyalty_automation_id' => $this->factionLoyaltyAutomationId,
                'completed_at' => $this->factionLoyaltyAutomation->completed_at,
            ]);

            Log::warning('Faction loyalty automation bailing: faction loyalty automation already completed.', [
                'character_id' => $this->characterId,
                'automation_id' => $this->automationId,
            ]);

            return true;
        }

        if (now()->greaterThanOrEqualTo($this->characterAutomation->completed_at)) {
            Log::channel('faction_loyalty')->warning('Faction loyalty job bailing: character automation expired.', [
                'character_id' => $this->characterId,
                'character_name' => $this->character->name,
                'automation_id' => $this->automationId,
                'faction_loyalty_automation_id' => $this->factionLoyaltyAutomationId,
                'completed_at' => $this->characterAutomation->completed_at,
            ]);

            Log::warning('Faction loyalty automation bailing: character automation expired.', [
                'character_id' => $this->characterId,
                'automation_id' => $this->automationId,
            ]);

            return true;
        }

        return false;
    }

    /**
     * Handle an automation exception.
     *
     * @param Exception $exception
     * @param CharacterCacheData $characterCacheData
     * @return void
     */
    private function handleAutomationException(Exception $exception, CharacterCacheData $characterCacheData): void
    {
        $context = [
            'character_id' => $this->characterId,
            'character_name' => $this->character?->name,
            'automation_id' => $this->automationId,
            'faction_loyalty_automation_id' => $this->factionLoyaltyAutomationId,
            'exception_class' => $exception::class,
            'exception_message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ];

        Log::error('Faction loyalty automation failed.', $context);
        Log::channel('faction_loyalty')->error('Faction loyalty automation failed.', $context);

        $this->sendOutEventLogUpdate(
            'Something went wrong with faction loyalty automation. Automation canceled.',
            true
        );

        $this->endAutomation($characterCacheData, false);
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
        if (! is_null($this->character) && $this->character->isLoggedIn()) {
            $this->safelyDispatchBroadcastEvent(
                new AutomationLogUpdate($this->character->user->id, $message, $makeItalic, $isReward),
                ['character_id' => $this->characterId, 'automation_id' => $this->automationId]
            );
        }
    }

    /**
     * End automation.
     *
     * @param CharacterCacheData $characterCacheData
     * @param bool $sendCompletionMessages
     * @return void
     */
    private function endAutomation(CharacterCacheData $characterCacheData, bool $sendCompletionMessages = true): void
    {
        if (is_null($this->character)) {
            return;
        }

        Log::channel('faction_loyalty')->info('Faction loyalty automation ending.', [
            'character_id' => $this->characterId,
            'character_name' => $this->character->name,
            'automation_id' => $this->automationId,
            'faction_loyalty_automation_id' => $this->factionLoyaltyAutomationId,
            'send_completion_messages' => $sendCompletionMessages,
            'character_automation_exists' => ! is_null($this->characterAutomation),
            'faction_loyalty_automation_exists' => ! is_null($this->factionLoyaltyAutomation),
        ]);

        Log::info('Faction loyalty automation ending.', [
            'character_id' => $this->characterId,
            'automation_id' => $this->automationId,
            'send_completion_messages' => $sendCompletionMessages,
        ]);

        $this->character = $this->setCharacterCanCraft($this->character, true);

        if (! is_null($this->factionLoyaltyAutomation)) {
            $this->factionLoyaltyAutomation->update([
                'completed_at' => now(),
            ]);
        }

        if (! is_null($this->characterAutomation)) {
            $characterCacheData->deleteCharacterSheet($this->character);

            $this->characterAutomation->update([
                'completed_at' => now(),
            ]);

            $this->characterAutomation->delete();

            $broadcastContext = ['character_id' => $this->characterId, 'automation_id' => $this->automationId];
            $this->safelyDispatchBroadcastEvent(new UpdateCharacterStatus($this->character), $broadcastContext);
            $this->safelyDispatchBroadcastEvent(new AutomationTimeOut($this->character->user, 0), $broadcastContext);
            $this->safelyDispatchBroadcastEvent(new AutomationStatus($this->character->user, false), $broadcastContext);

            if ($sendCompletionMessages) {
                $this->sendOutEventLogUpdate('The npc thanks you for your service.', true);

                $this->sendOutEventLogUpdate('You have stopped helping the npc and can take a breather and relax until you decide that you want to help them out again. Crafting has been re-enabled.', true);
            }

            return;
        }

        $this->character->currentAutomations()->delete();

        $broadcastContext = ['character_id' => $this->characterId, 'automation_id' => $this->automationId];
        $this->safelyDispatchBroadcastEvent(new UpdateCharacterStatus($this->character), $broadcastContext);
        $this->safelyDispatchBroadcastEvent(new AutomationTimeOut($this->character->user, 0), $broadcastContext);
        $this->safelyDispatchBroadcastEvent(new AutomationStatus($this->character->user, false), $broadcastContext);
    }

    /**
     * Set whether the character can craft.
     *
     * @param Character $character
     * @param bool $canCraft
     * @return Character
     */
    private function setCharacterCanCraft(Character $character, bool $canCraft): Character
    {
        $character->update([
            'can_craft' => $canCraft,
            'can_craft_again_at' => null,
        ]);

        return $character->refresh();
    }
}
