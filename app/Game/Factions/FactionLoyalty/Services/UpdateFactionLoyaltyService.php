<?php

namespace App\Game\Factions\FactionLoyalty\Services;

use App\Flare\Models\Character;
use App\Flare\Models\FactionLoyaltyNpc;
use App\Flare\Models\FactionLoyaltyNpcTask;
use App\Flare\Models\GameMap;
use App\Flare\Models\Monster;
use App\Flare\Values\MapNameValue;
use Illuminate\Support\Collection;

class UpdateFactionLoyaltyService
{
    /**
     * Update faction loyalty bounty tasks for a character.
     */
    public function updateFactionLoyaltyBountyTasks(Character $character): void
    {
        $gameMaps = GameMap::whereIn('name', [
            MapNameValue::DELUSIONAL_MEMORIES,
            MapNameValue::ICE_PLANE,
        ])->get();

        $characterFactions = $character->factions()->whereIn('game_map_id', $gameMaps->pluck('id')->toArray())->get();

        $characterFactionLoyalties = $character->factionLoyalties()->whereIn('faction_id', $characterFactions->pluck('id')->toArray())->get();

        foreach ($characterFactionLoyalties as $characterFactionLoyalty) {
            $factionLoyaltyNpcs = $characterFactionLoyalty->factionLoyaltyNpcs;

            $this->handleFactionLoyaltyNpcs($factionLoyaltyNpcs);
        }
    }

    /**
     * Handle the faction loyalty for npcs.
     */
    private function handleFactionLoyaltyNpcs(Collection $factionLoyaltyNpcs): void
    {
        foreach ($factionLoyaltyNpcs as $factionLoyaltyNpc) {
            $task = $factionLoyaltyNpc->factionLoyaltyNpcTasks;

            $this->handleTasksForNpc($factionLoyaltyNpc, $task);
        }
    }

    /**
     * Handle tasks for npc.
     */
    private function handleTasksForNpc(FactionLoyaltyNpc $factionLoyaltyNpc, FactionLoyaltyNpcTask $factionLoyaltyNpcTask): void
    {
        $tasks = $factionLoyaltyNpcTask->fame_tasks;

        $gameMapId = $factionLoyaltyNpc->factionLoyalty->faction->game_map_id;

        foreach ($tasks as $index => $task) {
            if ($task['type'] === 'bounty') {
                $monster = Monster::find($task['monster_id']);

                if ($monster->game_map_id !== $gameMapId) {
                    $newMonster = $this->fetchNewMonster($tasks, $gameMapId);

                    $task['monster_id'] = $newMonster->id;
                    $task['monster_name'] = $newMonster->name;

                    $tasks[$index] = $task;

                    continue;
                }
            }
        }

        $factionLoyaltyNpcTask->update([
            'fame_tasks' => $tasks,
        ]);
    }

    /**
     * Handle finding a new monster for the task.
     */
    private function fetchNewMonster(array $tasks, int $gameMapId): Monster
    {
        $monster = Monster::where('game_map_id', $gameMapId)
            ->where('is_raid_monster', false)
            ->where('is_raid_boss', false)
            ->where('is_celestial_entity', false)
            ->whereNull('only_for_location_type')
            ->inRandomOrder()
            ->first();

        if ($this->hasTaskAlready($tasks, 'monster_id', $monster->id)) {
            return $this->fetchNewMonster($tasks, $gameMapId);
        }

        return $monster;
    }

    /**
     * Check if the monster already has a task.
     */
    private function hasTaskAlready(array $tasks, string $key, int $id): bool
    {
        foreach ($tasks as $task) {
            if ($task['type'] === 'bounty') {
                if ($task[$key] === $id) {
                    return true;
                }
            }

        }

        return false;
    }
}
