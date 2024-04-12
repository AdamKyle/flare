<?php

namespace App\Game\Factions\FactionLoyalty\Services;


use App\Flare\Models\Character;
use App\Flare\Models\Event;
use App\Flare\Models\Faction;
use App\Flare\Models\FactionLoyalty;
use App\Flare\Models\FactionLoyaltyNpc;
use App\Flare\Models\FactionLoyaltyNpcTask;
use App\Flare\Models\Item;
use App\Flare\Models\Monster;
use App\Flare\Models\Npc;
use App\Flare\Values\MapNameValue;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Events\Values\EventType;
use Exception;

class FactionLoyaltyService {

    use ResponseBuilder;

    const CRAFTING_TYPES = [
        'weapon',
        'armour',
        'ring',
        'spell',
    ];

    /**
     * Get either the npc faction loyalty details that we are helping or the first one for the plane.
     *
     * @param Character $character
     * @return array
     */
    public function getLoyaltyInfoForPlane(Character $character): array {
        $factionLoyalties = $character->factionLoyalties;

        $factionLoyalty   = [];

        if ($factionLoyalties->isNotEmpty()) {
            $factionLoyalty = $factionLoyalties->where('is_pledged', true)->first();
        }

        if (empty($factionLoyalty)) {
            return $this->errorResult('You have not pledged to a faction.');
        }

        $npcNames = $factionLoyalty->factionLoyaltyNpcs->map(function($factionNpc) {
            return [
                'id' => $factionNpc->npc_id,
                'name' => $factionNpc->npc->real_name,
            ];
        })->toArray();

        return $this->successResult([
            'npcs'            => $npcNames,
            'faction_loyalty' => $factionLoyalty,
            'map_name'        => $factionLoyalty->faction->gameMap->name,
        ]);
    }

    /**
     * Remove the pledge.
     *
     * @param Character $character
     * @param Faction $faction
     * @return array
     */
    public function removePledge(Character $character, Faction $faction): array {
        $factionLoyalty = $character->factionLoyalties()->where('faction_id', $faction->id)->first();

        if (!is_null($factionLoyalty)) {
            $factionLoyalty->update([
                'is_pledged' => false,
            ]);

            $factionLoyalty->factionLoyaltyNpcs()->update([
                'currently_helping' => false,
            ]);

            return $this->successResult([
                'message' => 'No longer pledged to: ' . $faction->gameMap->name . '.',
                'factions' => $character->refresh()->factions->transform(function($faction) {
                    $faction->map_name   = $faction->gameMap->name;
                    $faction->is_pledged = $faction->character->factionLoyalties()->where('is_pledged', true)->exists();

                    return $faction;
                })
            ]);
        }

        return $this->errorResult('Failed to find the faction you are pledged to.');
    }

    /**
     * Assist a Npc.
     *
     * @param Character $character
     * @param FactionLoyaltyNpc $factionLoyaltyNpc
     * @return array
     */
    public function assistNpc(Character $character, FactionLoyaltyNpc $factionLoyaltyNpc): array {
        if ($factionLoyaltyNpc->factionLoyalty->character_id !== $character->id) {
            return $this->errorResult('Nope. Not allowed.');
        }

        $foundLoyalty = $factionLoyaltyNpc->factionLoyalty;

        $foundLoyalty->factionLoyaltyNpcs()->update(['currently_helping' => false]);

        $factionLoyaltyNpc->update([
            'currently_helping' => true,
        ]);

        $factionLoyaltyNpc = $factionLoyaltyNpc->refresh();

        $result = $this->getLoyaltyInfoForPlane($character->refresh());

        return $this->successResult([
            'message' => 'You are now assisting ' . $factionLoyaltyNpc->npc->real_name . ' with their tasks!',
            'faction_loyalty' => $result['faction_loyalty'],
        ]);
    }

    /**
     * Stop Assisting NPC.
     *
     * @param Character $character
     * @param FactionLoyaltyNpc $factionLoyaltyNpc
     * @return array
     */
    public function stopAssistingNpc(Character $character, FactionLoyaltyNpc $factionLoyaltyNpc): array {
        if ($factionLoyaltyNpc->factionLoyalty->character_id !== $character->id) {
            return $this->errorResult('Nope. Not allowed.');
        }

        $foundLoyalty = $factionLoyaltyNpc->factionLoyalty;

        $foundLoyalty->factionLoyaltyNpcs()->update(['currently_helping' => false]);

        $result = $this->getLoyaltyInfoForPlane($character->refresh());

        return $this->successResult([
            'message' => 'You stopped assisting ' . $factionLoyaltyNpc->npc->real_name . ' with their tasks. They are sad but understand.',
            'faction_loyalty' => $result['faction_loyalty'],
        ]);
    }

    /**
     * Pledge to a plain and create the approproate tasks and npc mappings.
     *
     * @param Character $character
     * @param Faction $faction
     * @return array
     * @throws Exception
     */
    public function pledgeLoyalty(Character $character, Faction $faction): array {

        if ($faction->character_id !== $character->id) {
            return $this->errorResult('Nope. Not allowed.');
        }

        if (!$faction->maxed) {
            return $this->errorResult('You must level the faction to level 5 before being able to assist the fine people of this plane with their tasks.');
        }

        $factionLoyalty = $character->factionLoyalties()->where('faction_id', $faction->id)->first();

        if (!is_null($factionLoyalty)) {
            $character->factionLoyalties()->update([
                'is_pledged' => false,
            ]);

            $character->factionLoyalties->each(function ($faction) {
                $faction->factionLoyaltyNpcs()->update(['currently_helping' => false]);
            });

            $character = $character->refresh();

            $factionLoyalty = $factionLoyalty->refresh();

            $factionLoyalty->update([
                'is_pledged' => true,
            ]);

            $factionLoyalty = $factionLoyalty->refresh();
        } else {
            $character->factionLoyalties()->update([
                'is_pledged' => false,
            ]);

            $character->factionLoyalties->each(function ($faction) {
                $faction->factionLoyaltyNpcs()->update(['currently_helping' => false]);
            });

            $character = $character->refresh();

            $factionLoyalty = FactionLoyalty::create([
                'character_id' => $character->id,
                'faction_id'   => $faction->id,
                'is_pledged'   => true,
            ]);

            $this->createNpcsForLoyalty($factionLoyalty);
        }

        return $this->successResult([
            'message'  => 'Pledged to: ' . $factionLoyalty->faction->gameMap->name . '.',
            'factions' => $character->refresh()->factions->transform(function($faction) {
                $faction->map_name   = $faction->gameMap->name;
                $faction->is_pledged = $faction->character->factionloyalties()->where('is_pledged', true)->exists();

                return $faction;
            })
        ]);
    }

    /**
     * Creates new tasks for the Faction Npc Tasks.
     *
     * @param FactionLoyaltyNpcTask $factionLoyaltyNpcTask
     * @return FactionLoyaltyNpcTask
     * @throws Exception
     */
    public function createNewTasksForNpc(FactionLoyaltyNpcTask $factionLoyaltyNpcTask): FactionLoyaltyNpcTask {
        $npc = $factionLoyaltyNpcTask->factionLoyaltyNpc->npc;

        $craftingTasks = $this->createCraftingTasks($npc->gameMap->name);
        $bountyTasks   = $this->createBountyTasks($npc->game_map_id);

        $tasks = array_merge($craftingTasks, $bountyTasks);

        $factionLoyaltyNpcTask->update([
            'fame_tasks' => $tasks,
        ]);

        $factionLoyaltyNpcTask = $factionLoyaltyNpcTask->refresh();

        $factionLoyaltyNpcTask->factionLoyaltyNpc()->update([
            'next_level_fame' => collect($craftingTasks)->sum('required_amount') +
                collect($bountyTasks)->sum('required_amount'),
        ]);

        return $factionLoyaltyNpcTask->refresh();
    }

    /**
     * Create NPC For Loyalty.
     *
     * @param FactionLoyalty $factionLoyalty
     * @return void
     * @throws Exception
     */
    protected function createNpcsForLoyalty(FactionLoyalty $factionLoyalty) {
        $npcs = Npc::where('game_map_id', $factionLoyalty->faction->game_map_id)->get();

        $totalNpcFame = (1 / $npcs->count()) / 25;

        foreach ($npcs as $npc) {

            $craftingTasks = $this->createCraftingTasks($npc->gameMap->name);
            $bountyTasks   = $this->createBountyTasks($npc->game_map_id);

            $factionLoyaltyNpc = FactionLoyaltyNpc::create([
                'faction_loyalty_id'         => $factionLoyalty->id,
                'npc_id'                     => $npc->id,
                'current_level'              => 0,
                'max_level'                  => 25,
                'next_level_fame'            => collect($craftingTasks)->sum('required_amount') +
                    collect($bountyTasks)->sum('required_amount'),
                'kingdom_item_defence_bonus' => $totalNpcFame,
                'currently_helping'          => false,
            ]);

            FactionLoyaltyNpcTask::create([
                'faction_loyalty_id'     => $factionLoyalty->id,
                'faction_loyalty_npc_id' => $factionLoyaltyNpc->id,
                'fame_tasks'             => array_merge($bountyTasks, $craftingTasks)
            ]);
        }
    }

    /**
     * Create three crafting tasks.
     *
     * @param string $gameMapName
     * @return array
     * @throws Exception
     */
    protected function createCraftingTasks(string $gameMapName): array {
        $tasks       = [];

        while (count($tasks) < 3) {

            $craftingType = self::CRAFTING_TYPES[rand(0, count(self::CRAFTING_TYPES) - 1)];

            $item = $this->getItemForCraftingTask($craftingType, $gameMapName);

            if ($this->hasTaskAlready($tasks, 'item_id', $item->id)) {
                continue;
            }

            $amount = rand(10, 50);

            $event = Event::where('type', EventType::WEEKLY_FACTION_LOYALTY_EVENT)->first();

            if (is_null($event)) {
                $amount = ceil($amount / 2);
            }

            $tasks[] = [
                'type'            => $item->type,
                'item_name'       => $item->affix_name,
                'item_id'         => $item->id,
                'required_amount' => $amount,
                'current_amount'  => 0,
            ];
        }

        return $tasks;
    }

    /**
     * Create three bounty tasks.
     *
     * @param int $gameMapId
     * @return array
     */
    protected function createBountyTasks(int $gameMapId): array {
        $tasks = [];

        while (count($tasks) < 3) {

            $monster = Monster::where('game_map_id', $gameMapId)
                ->where('is_raid_monster', false)
                ->where('is_raid_boss', false)
                ->where('is_celestial_entity', false)
                ->inRandomOrder()
                ->first();

            if ($this->hasTaskAlready($tasks, 'monster_id', $monster->id)) {
                continue;
            }

            $amount = rand(10, 50);

            $event = Event::where('type', EventType::WEEKLY_FACTION_LOYALTY_EVENT)->first();

            if (is_null($event)) {
                $amount = ceil($amount / 2);
            }

            $tasks[] = [
                'type'            => 'bounty',
                'monster_name'    => $monster->name,
                'monster_id'      => $monster->id,
                'required_amount' => $amount,
                'current_amount'  => 0,
            ];
        }

        return $tasks;
    }

    private function hasTaskAlready(array $tasks, string $key, int $id): bool {
        foreach ($tasks as $task) {
            if ($task[$key] === $id) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get items for crafting.
     *
     * - Make sure its level appropriate for the plane.
     *
     * @param string $type
     * @param string $gamMapName
     * @return Item
     * @throws Exception
     */
    private function getItemForCraftingTask(string $type, string $gamMapName): Item {

        $gameMapValue = new MapNameValue($gamMapName);

        $item = Item::inRandomOrder()->doesntHave('itemSuffix')
            ->doesntHave('itemPrefix')
            ->whereNotIn('type', ['quest', 'alchemy', 'trinket', 'artifact'])
            ->whereNull('specialty_type');

        if ($gameMapValue->isSurface()) {
            $item->where('skill_level_required', '<=', 50);
        }

        if ($gameMapValue->isLabyrinth()) {
            $item->where('skill_level_required', '<=', 150);
        }

        if ($gameMapValue->isDungeons()) {
            $item->where('skill_level_required', '<=', 240);
        }

        if ($gameMapValue->isHell()) {
            $item->where('skill_level_required', '<=', 300);
        }

        return $item->where('crafting_type', $type)->first();
    }
}
