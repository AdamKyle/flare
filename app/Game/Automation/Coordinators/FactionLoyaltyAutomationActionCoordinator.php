<?php

namespace App\Game\Automation\Coordinators;

use App\Flare\Models\FactionLoyaltyAutomation;
use App\Flare\Models\FactionLoyaltyNpc;
use App\Game\Automation\Enums\FactionLoyaltyCoordinatorAction;

class FactionLoyaltyAutomationActionCoordinator
{
    private FactionLoyaltyAutomation $factionLoyaltyAutomation;

    private FactionLoyaltyNpc $factionLoyaltyNpc;

    /**
     * Set up the coordinator.
     *
     * @param FactionLoyaltyAutomation $factionLoyaltyAutomation
     * @param FactionLoyaltyNpc $factionLoyaltyNpc
     * @return FactionLoyaltyAutomationActionCoordinator
     */
    public function setUp(FactionLoyaltyAutomation $factionLoyaltyAutomation, FactionLoyaltyNpc $factionLoyaltyNpc): FactionLoyaltyAutomationActionCoordinator
    {
        $this->factionLoyaltyAutomation = $factionLoyaltyAutomation;
        $this->factionLoyaltyNpc = $factionLoyaltyNpc;

        return $this;
    }

    /**
     * Resolve the next automation action.
     *
     * @return array|null
     */
    public function resolveAction(): ?array
    {
        $incompleteTasks = $this->getIncompleteTasks();

        if (empty($incompleteTasks)) {
            return null;
        }

        $failedCraftingTask = $this->getFailedCraftingTask($incompleteTasks);

        if (! is_null($failedCraftingTask)) {
            return $this->buildCraftAction($failedCraftingTask);
        }

        $failedBountyTask = $this->getFailedBountyTask($incompleteTasks);

        if (! is_null($failedBountyTask)) {
            return $this->buildFightAction($failedBountyTask);
        }

        $craftingTask = $this->getCraftingTask($incompleteTasks);
        $bountyTask = $this->getBountyTask($incompleteTasks);

        if (! is_null($craftingTask) && ! is_null($bountyTask)) {
            return $this->resolveRotatingAction($craftingTask, $bountyTask);
        }

        if (! is_null($craftingTask)) {
            return $this->buildCraftAction($craftingTask);
        }

        if (! is_null($bountyTask)) {
            return $this->buildFightAction($bountyTask);
        }

        return null;
    }

    /**
     * Get incomplete tasks.
     *
     * @return array
     */
    private function getIncompleteTasks(): array
    {
        $factionLoyaltyNpcTask = $this->factionLoyaltyNpc->factionLoyaltyNpcTasks;

        if (is_null($factionLoyaltyNpcTask)) {
            return [];
        }

        return array_values(collect($factionLoyaltyNpcTask->fame_tasks)->filter(function (array $task): bool {
            return $task['current_amount'] < $task['required_amount'];
        })->toArray());
    }

    /**
     * Get failed crafting task.
     *
     * @param array $incompleteTasks
     * @return array|null
     */
    private function getFailedCraftingTask(array $incompleteTasks): ?array
    {
        if (is_null($this->factionLoyaltyAutomation->failed_crafting_item_id)) {
            return null;
        }

        foreach ($incompleteTasks as $incompleteTask) {
            if (! isset($incompleteTask['item_id'])) {
                continue;
            }

            if ($incompleteTask['item_id'] === $this->factionLoyaltyAutomation->failed_crafting_item_id) {
                return $incompleteTask;
            }
        }

        return null;
    }

    /**
     * Get failed bounty task.
     *
     * @param array $incompleteTasks
     * @return array|null
     */
    private function getFailedBountyTask(array $incompleteTasks): ?array
    {
        if (is_null($this->factionLoyaltyAutomation->failed_bounty_monster_id)) {
            return null;
        }

        foreach ($incompleteTasks as $incompleteTask) {
            if ($incompleteTask['type'] !== 'bounty') {
                continue;
            }

            if ($incompleteTask['monster_id'] === $this->factionLoyaltyAutomation->failed_bounty_monster_id) {
                return $incompleteTask;
            }
        }

        return null;
    }

    /**
     * Get crafting task.
     *
     * @param array $incompleteTasks
     * @return array|null
     */
    private function getCraftingTask(array $incompleteTasks): ?array
    {
        foreach ($incompleteTasks as $incompleteTask) {
            if (isset($incompleteTask['item_id'])) {
                return $incompleteTask;
            }
        }

        return null;
    }

    /**
     * Get bounty task.
     *
     * @param array $incompleteTasks
     * @return array|null
     */
    private function getBountyTask(array $incompleteTasks): ?array
    {
        foreach ($incompleteTasks as $incompleteTask) {
            if ($incompleteTask['type'] === 'bounty') {
                return $incompleteTask;
            }
        }

        return null;
    }

    /**
     * Resolve rotating action.
     *
     * @param array $craftingTask
     * @param array $bountyTask
     * @return array
     */
    private function resolveRotatingAction(array $craftingTask, array $bountyTask): array
    {
        $lastAction = $this->getLastAction();

        if ($lastAction === FactionLoyaltyCoordinatorAction::CRAFT) {
            return $this->buildFightAction($bountyTask);
        }

        if ($lastAction === FactionLoyaltyCoordinatorAction::FIGHT) {
            return $this->buildCraftAction($craftingTask);
        }

        return $this->buildCraftAction($craftingTask);
    }

    /**
     * Get last automation action.
     *
     * @return FactionLoyaltyCoordinatorAction|null
     */
    private function getLastAction(): ?FactionLoyaltyCoordinatorAction
    {
        $log = $this->factionLoyaltyAutomation->log;

        if (is_null($log)) {
            return null;
        }

        $lastCraftingLog = collect($log->crafting_logs ?? [])->last();
        $lastFightLog = collect($log->fight_logs ?? [])->last();

        if (is_null($lastCraftingLog) && is_null($lastFightLog)) {
            return null;
        }

        if (is_null($lastFightLog)) {
            return FactionLoyaltyCoordinatorAction::CRAFT;
        }

        if (is_null($lastCraftingLog)) {
            return FactionLoyaltyCoordinatorAction::FIGHT;
        }

        if (($lastCraftingLog['created_at'] ?? null) > ($lastFightLog['created_at'] ?? null)) {
            return FactionLoyaltyCoordinatorAction::CRAFT;
        }

        return FactionLoyaltyCoordinatorAction::FIGHT;
    }

    /**
     * Build craft action.
     *
     * @param array $task
     * @return array
     */
    private function buildCraftAction(array $task): array
    {
        return [
            'type' => FactionLoyaltyCoordinatorAction::CRAFT->value,
            'task' => $task,
        ];
    }

    /**
     * Build fight action.
     *
     * @param array $task
     * @return array
     */
    private function buildFightAction(array $task): array
    {
        return [
            'type' => FactionLoyaltyCoordinatorAction::FIGHT->value,
            'task' => $task,
        ];
    }
}