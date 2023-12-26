<?php

namespace App\Game\Factions\FactionLoyalty\Services;


use App\Flare\Models\Character;
use App\Flare\Models\Faction;
use App\Flare\Models\FactionLoyalty;
use App\Flare\Models\FactionLoyaltyNpc;
use App\Flare\Models\FactionLoyaltyNpcTask;
use App\Flare\Models\Item;
use App\Flare\Models\Monster;
use App\Flare\Models\Npc;
use App\Flare\Values\MapNameValue;
use App\Game\Core\Traits\ResponseBuilder;

class FactionLoyaltyService {

    use ResponseBuilder;

    const CRAFTING_TYPES = [
        'weapon',
        'armour',
        'ring',
        'spell',
    ];

    public function getLoyaltyInfoForPlane(Character $character): array {
        $gameMap = $character->map->gameMap;

        $npcNames = Npc::where('game_map_id', $gameMap->id)->get()->map(function($npc) {
            return [
                'id'   => $npc->id,
                'name' => $npc->real_name
            ];
        });

        return $this->successResult([
            'npcs' => $npcNames,
            'map_name' => $gameMap->name,
        ]);
    }

    public function pledgeLoyalty(Character $character, Faction $faction): array {
        $factionLoyalty = FactionLoyalty::create([
            'character_id' => $character->id,
            'faction_id'   => $faction->id,
        ]);

        $this->createNpcsForLoyalty($character, $factionLoyalty);

        return $this->successResult([
            'message' => 'Pledged to: ' . $factionLoyalty->faction->gameMap->name . '.',
        ]);
    }

    protected function createNpcsForLoyalty(Character $character, FactionLoyalty $factionLoyalty) {
        $npcs = Npc::where('game_map_id', $character->map->game_map_id)->get();

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
                'kingdom_item_defence_bonus' => $totalNpcFame
            ]);

            FactionLoyaltyNpcTask::create([
                'faction_loyalty_id'     => $factionLoyalty->id,
                'faction_loyalty_npc_id' => $factionLoyaltyNpc->id,
                'currently_helping'      => false,
                'fame_tasks'             => array_merge($bountyTasks, $craftingTasks)
            ]);
        }
    }

    protected function createCraftingTasks(string $gameMapName): array {
        $tasks       = [];

        for ($i = 1; $i <= 3; $i++) {

            $craftingType = self::CRAFTING_TYPES[rand(0, count(self::CRAFTING_TYPES) - 1)];

            $item = $this->getItemForCraftingTask($craftingType, $gameMapName);

            $tasks[] = [
                'type'            => $craftingType,
                'item_name'       => $item->affix_name,
                'item_id'         => $item->id,
                'required_amount' => rand(10, 50),
                'current_amount'  => 0,
            ];
        }

        return $tasks;
    }

    protected function createBountyTasks(int $gameMapId): array {
        $tasks = [];

        for ($i = 1; $i <= 3; $i++) {

            $monster = Monster::where('game_map_id', $gameMapId)
                ->where('is_raid_monster', false)
                ->where('is_raid_boss', false)
                ->inRandomOrder()
                ->first();

            $tasks[] = [
                'type'            => 'bounty',
                'monster_name'    => $monster->name,
                'monster_id'      => $monster->id,
                'required_amount' => rand(10, 50),
                'current_amount'  => 0,
            ];
        }

        return $tasks;
    }

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
