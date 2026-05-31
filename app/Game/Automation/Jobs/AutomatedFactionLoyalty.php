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
use App\Game\Factions\FactionLoyalty\Events\FactionLoyaltyAutomationWarningState;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AutomatedFactionLoyalty implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $characterId;

    public int $automationId;

    public int $factionLoyaltyAutomationId;

    public int $timeDelay;

    private ?Character $character = null;

    private ?CharacterAutomation $characterAutomation = null;

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
        $this->character = Character::find($this->characterId);

        $this->characterAutomation = CharacterAutomation::where('character_id', $this->characterId)
            ->where('id', $this->automationId)
            ->first();

        $this->factionLoyaltyAutomation = FactionLoyaltyAutomation::where('character_id', $this->characterId)
            ->where('id', $this->factionLoyaltyAutomationId)
            ->first();

        if ($this->shouldBail()) {
            $this->endAutomation($characterCacheData);

            return;
        }

        try {
            $this->factionLoyaltyNpc = $this->resolveFactionLoyaltyNpc($factionLoyaltyNpcTaskCoordinator);

            if (is_null($this->factionLoyaltyNpc) || $factionLoyaltyNpcTaskCoordinator->shouldEndAutomation()) {
                $this->endAutomation($characterCacheData, false);

                return;
            }

            $factionLoyaltyAutomationAction = $this->resolveFactionLoyaltyAutomationAction($factionLoyaltyAutomationActionCoordinator);

            if (is_null($factionLoyaltyAutomationAction)) {
                $this->sendOutEventLogUpdate(
                    'No faction loyalty automation action could be resolved. Automation canceled.',
                    true
                );

                $this->endAutomation($characterCacheData, false);

                return;
            }

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

        $automatedCraftingResult = $automatedCraftingHandler
            ->setUp(
                $this->character,
                $task['item_id'],
                $factionLoyaltyAutomationCraftingLogger->setUp($this->factionLoyaltyAutomation)
            )
            ->setCraftForNpc()
            ->setFactionLoyaltyNpc($this->factionLoyaltyNpc)
            ->handle();

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

        event(new FactionLoyaltyAutomationWarningState(
            $this->character->user,
            count($warningNotices) > 0,
            $warningNotices
        ));
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
            $this->endAutomation($characterCacheData);

            return;
        }

        AutomatedFactionLoyalty::dispatch(
            $this->characterId,
            $this->automationId,
            $this->factionLoyaltyAutomationId,
            $this->timeDelay
        )->delay(now()->addMinutes($this->timeDelay))->onQueue('default_long');
    }

    /**
     * Should the job bail?
     *
     * @return bool
     */
    private function shouldBail(): bool
    {
        if (is_null($this->character)) {
            return true;
        }

        if (is_null($this->characterAutomation) || is_null($this->factionLoyaltyAutomation)) {
            return true;
        }

        if (! is_null($this->factionLoyaltyAutomation->completed_at)) {
            return true;
        }

        if (now()->greaterThanOrEqualTo($this->characterAutomation->completed_at)) {
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
        Log::error('Faction loyalty automation failed.', [
            'character_id' => $this->characterId,
            'automation_id' => $this->automationId,
            'faction_loyalty_automation_id' => $this->factionLoyaltyAutomationId,
            'exception' => $exception,
        ]);

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
            event(new AutomationLogUpdate($this->character->user->id, $message, $makeItalic, $isReward));
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

            event(new UpdateCharacterStatus($this->character));
            event(new AutomationTimeOut($this->character->user, 0));
            event(new AutomationStatus($this->character->user, false));

            if ($sendCompletionMessages) {
                $this->sendOutEventLogUpdate('The npc thanks you for your service.', true);

                $this->sendOutEventLogUpdate('You have stopped helping the npc and can take a breather and relax until you decide that you want to help them out again. Crafting has been re-enabled.', true);
            }

            return;
        }

        $this->character->currentAutomations()->delete();

        event(new UpdateCharacterStatus($this->character));
        event(new AutomationTimeOut($this->character->user, 0));
        event(new AutomationStatus($this->character->user, false));
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
