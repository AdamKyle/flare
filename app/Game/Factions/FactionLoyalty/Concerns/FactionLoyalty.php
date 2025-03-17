<?php

namespace App\Game\Factions\FactionLoyalty\Concerns;

use App\Flare\Models\Character;
use App\Flare\Models\Event;
use App\Flare\Models\FactionLoyalty as FactionLoyaltyModel;
use App\Flare\Models\FactionLoyaltyNpc;
use App\Game\Events\Values\EventType;

trait FactionLoyalty
{
    private bool $updatedMatchingTaskAmount = false;

    /**
     * Get faction loyalty that the character is pledged to.
     */
    public function getFactionLoyalty(Character $character): ?FactionLoyaltyModel
    {
        return $character->factionLoyalties()->where('is_pledged', true)->first();
    }

    /**
     * Get the current npc that the player is helping.
     */
    public function getNpcCurrentlyHelping(FactionLoyaltyModel $factionLoyalty): ?FactionLoyaltyNpc
    {
        return $factionLoyalty->factionLoyaltyNpcs->where('currently_helping', true)->first();
    }

    /**
     * Does the NPC have a matching task?
     */
    public function hasMatchingTask(FactionLoyaltyNpc $helpingNpc, string $key, int $id): bool
    {
        return collect($helpingNpc->factionLoyaltyNpcTasks->fame_tasks)->filter(function ($task) use ($key, $id) {
            return isset($task[$key]) && $task[$key] === $id;
        })->isNotEmpty();
    }

    /**
     * Updates a matching helping task.
     */
    public function updateMatchingHelpTask(FactionLoyaltyNpc $helpingNpc, string $key, int $id): FactionLoyaltyNpc
    {

        $existingFame = $helpingNpc->current_fame;
        $tasks = $helpingNpc->factionLoyaltyNpcTasks->fame_tasks;

        foreach ($tasks as $index => $task) {
            if (isset($task[$key]) && $task[$key] === $id) {
                $amount = min($task['current_amount'] + 1, $task['required_amount']);

                $event = Event::where('type', EventType::WEEKLY_FACTION_LOYALTY_EVENT)->first();

                if (! is_null($event)) {
                    $amount = min($task['current_amount'] + 2, $task['required_amount']);
                }

               $tasks[$index]['current_amount'] = $amount;
            }
        }

        $helpingNpc->factionLoyaltyNpcTasks()->update([
            'fame_tasks' => $tasks,
        ]);

        $helpingNpc = $helpingNpc->refresh();

        if ($existingFame < $helpingNpc->current_fame) {
            $this->updatedMatchingTaskAmount = true;
        }

        return $helpingNpc->refresh();
    }

    /**
     * get the matching task.
     */
    public function getMatchingTask(FactionLoyaltyNpc $helpingNpc, $key, $id): array
    {
        return current(array_filter($helpingNpc->factionLoyaltyNpcTasks->fame_tasks, function ($task) use ($key, $id) {
            return isset($task[$key]) && $task[$key] === $id;
        })) ?: [];
    }

    /**
     * Was the current matching task, if any fund, updated?
     */
    public function wasCurrentFameForTaskUpdated(): bool
    {
        return $this->updatedMatchingTaskAmount;
    }

    /**
     * Should we show the npc craft button?
     */
    public function showCraftForNpcButton(Character $character, string|array $craftingType): bool
    {
        $pledgedFaction = $character->factionLoyalties()->where('is_pledged', true)->first();

        if (is_null($pledgedFaction)) {
            return false;
        }

        $assistingNpc = $pledgedFaction->factionLoyaltyNpcs()->where('currently_helping', true)->first();

        if (is_null($assistingNpc)) {
            return false;
        }

        $craftingTypeArray = is_array($craftingType) ? $craftingType : [$craftingType];

        return collect($assistingNpc->factionLoyaltyNpcTasks->fame_tasks)
            ->pluck('type')
            ->intersect($craftingTypeArray)
            ->isNotEmpty();
    }
}
