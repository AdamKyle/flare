<?php

namespace App\Game\Automation\FactionLoyalty\Coordinators;

use App\Flare\Models\FactionLoyaltyAutomation;
use App\Flare\Models\FactionLoyaltyNpc;
use App\Game\Automation\FactionLoyalty\Enums\FactionLoyaltyCoordinatorAction;

class FactionLoyaltyAutomationActionCoordinator
{
    private FactionLoyaltyAutomation $factionLoyaltyAutomation;

    private FactionLoyaltyNpc $factionLoyaltyNpc;

    /**
     * Set up the coordinator.
     *
     * @param FactionLoyaltyAutomation $factionLoyaltyAutomation The Faction Loyalty automation record.
     * @param FactionLoyaltyNpc $factionLoyaltyNpc The NPC being assisted.
     * @return FactionLoyaltyAutomationActionCoordinator The configured coordinator instance.
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
     * @return array|null The next action to perform, or null when no task is available.
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
     * @return array The NPC's incomplete fame tasks.
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
     * @param array $incompleteTasks The NPC's incomplete fame tasks.
     * @return array|null The previously failed crafting task, if any.
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
     * @param array $incompleteTasks The NPC's incomplete fame tasks.
     * @return array|null The previously failed bounty task, if any.
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
     * @param array $incompleteTasks The NPC's incomplete fame tasks.
     * @return array|null The next available crafting task, if any.
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
     * @param array $incompleteTasks The NPC's incomplete fame tasks.
     * @return array|null The next available bounty task, if any.
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
     * @param array $craftingTask The available crafting task.
     * @param array $bountyTask The available bounty task.
     * @return array The resolved action, alternating with the last performed action.
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
     * @return FactionLoyaltyCoordinatorAction|null The last performed automation action, if any.
     */
    private function getLastAction(): ?FactionLoyaltyCoordinatorAction
    {
        if (is_null($this->factionLoyaltyAutomation->last_automation_action)) {
            return null;
        }

        if ($this->factionLoyaltyAutomation->last_automation_action === FactionLoyaltyCoordinatorAction::CRAFT->value) {
            return FactionLoyaltyCoordinatorAction::CRAFT;
        }

        if ($this->factionLoyaltyAutomation->last_automation_action === FactionLoyaltyCoordinatorAction::FIGHT->value) {
            return FactionLoyaltyCoordinatorAction::FIGHT;
        }

        return null;
    }

    /**
     * Build craft action.
     *
     * @param array $task The crafting task.
     * @return array The built craft action.
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
     * @param array $task The bounty task.
     * @return array The built fight action.
     */
    private function buildFightAction(array $task): array
    {
        return [
            'type' => FactionLoyaltyCoordinatorAction::FIGHT->value,
            'task' => $task,
        ];
    }
}
