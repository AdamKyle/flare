<?php

namespace App\Console\AfterDeployment;

use App\Flare\Models\Character;
use App\Flare\Models\Event;
use App\Flare\Models\FactionLoyalty;
use App\Flare\Models\FactionLoyaltyNpc;
use App\Flare\Models\FactionLoyaltyNpcTask;
use App\Flare\Models\GameMap;
use App\Flare\Models\Item;
use App\Flare\Models\Monster;
use App\Flare\Models\Npc;
use App\Flare\Values\ItemEffectsValue;
use App\Flare\Values\MapNameValue;
use App\Game\Events\Values\EventType;
use Exception;
use Illuminate\Console\Command;

class AssignNewNpcsToFactionLoyalty extends Command
{
    const CRAFTING_TYPES = [
        'weapon',
        'armour',
        'ring',
        'spell',
    ];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'assign:new-npcs-to-faction-loyalty';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assigns new npcs to existing faction loyalties, pledged or not - assuming you have pledged';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        FactionLoyalty::chunkById(50, function ($factionLoyalties) {

            foreach ($factionLoyalties as $factionLoyalty) {
                $this->updateNpcsForFactionLOyalty($factionLoyalty);
            }
        });
    }

    /**
     * Update NPC's for faction loyalty
     *
     * @param FactionLoyalty $factionLoyalty
     * @return void
     */
    private function updateNpcsForFactionLOyalty(FactionLoyalty $factionLoyalty)
    {

        $character = $factionLoyalty->character;

        $npcs = Npc::where('game_map_id', $factionLoyalty->faction->game_map_id)->get();

        foreach ($npcs as $npc) {

            $hasNpc = $factionLoyalty->factionLoyaltyNpcs()->where('npc_id', $npc->id)->exists();

            if (! $hasNpc) {
                $craftingTasks = $this->createCraftingTasks($npc->gameMap->name);
                $bountyTasks = $this->createBountyTasks($character, $npc->gameMap);

                $factionLoyaltyNpc = FactionLoyaltyNpc::create([
                    'faction_loyalty_id' => $factionLoyalty->id,
                    'npc_id' => $npc->id,
                    'current_level' => 0,
                    'max_level' => 25,
                    'next_level_fame' => collect($craftingTasks)->sum('required_amount') +
                        collect($bountyTasks)->sum('required_amount'),
                    'kingdom_item_defence_bonus' => 0.025,
                    'currently_helping' => false,
                ]);

                FactionLoyaltyNpcTask::create([
                    'faction_loyalty_id' => $factionLoyalty->id,
                    'faction_loyalty_npc_id' => $factionLoyaltyNpc->id,
                    'fame_tasks' => array_merge($bountyTasks, $craftingTasks),
                ]);

                $this->info('Created for: ' . $npc->real_name);
            }
        }

        $npcs = FactionLoyaltyNpc::where('faction_loyalty_id', $factionLoyalty->id)->get();

        $totalNpcKingdomItemDefencePerLevel = (.95 / $npcs->count()) / 25;

        foreach ($npcs as $npc) {
            $npc->update([
                'kingdom_item_defence_bonus' => $totalNpcKingdomItemDefencePerLevel,
            ]);
        }
    }

    /**
     * Create the crafting tasks
     *
     * @param string $gameMapName
     * @return array
     */
    private function createCraftingTasks(string $gameMapName): array
    {
        $tasks = [];

        while (count($tasks) < 3) {

            $craftingType = self::CRAFTING_TYPES[rand(0, count(self::CRAFTING_TYPES) - 1)];

            $item = $this->getItemForCraftingTask($craftingType, $gameMapName);

            if ($this->hasTaskAlready($tasks, 'item_id', $item->id)) {
                continue;
            }

            $amount = rand(10, 50);

            $event = Event::where('type', EventType::WEEKLY_FACTION_LOYALTY_EVENT)->first();

            if (! is_null($event)) {
                $amount = ceil($amount / 2);
            }

            if ($amount <= 0) {
                $amount = 5;
            }

            $tasks[] = [
                'type' => $item->type,
                'item_name' => $item->affix_name,
                'item_id' => $item->id,
                'required_amount' => $amount,
                'current_amount' => 0,
            ];
        }

        return $tasks;
    }

    /**
     * Create bounty tasks
     *
     * @param Character $character
     * @param GameMap $gameMap
     * @return array
     */
    private function createBountyTasks(Character $character, GameMap $gameMap): array
    {

        $tasks = [];

        $gameMapId = $gameMap->id;

        if (! is_null($gameMap->only_during_event_type)) {

            $hasPurgatoryItem = $character->inventory->slots->filter(function ($slot) {
                return $slot->item->type === 'quest' && $slot->item->effect === ItemEffectsValue::PURGATORY;
            })->first();

            if (is_null($hasPurgatoryItem)) {
                $gameMapId = GameMap::where('name', MapNameValue::SURFACE)->first()->id;
            }
        }

        while (count($tasks) < 3) {

            $monster = Monster::where('game_map_id', $gameMapId)
                ->where('is_raid_monster', false)
                ->where('is_raid_boss', false)
                ->where('is_celestial_entity', false)
                ->whereNull('only_for_location_type')
                ->inRandomOrder()
                ->first();

            if ($this->hasTaskAlready($tasks, 'monster_id', $monster->id)) {
                continue;
            }

            $amount = rand(10, 50);

            $event = Event::where('type', EventType::WEEKLY_FACTION_LOYALTY_EVENT)->first();

            if (! is_null($event)) {
                $amount = ceil($amount / 2);
            }

            if ($amount <= 0) {
                $amount = 5;
            }

            $tasks[] = [
                'type' => 'bounty',
                'monster_name' => $monster->name,
                'monster_id' => $monster->id,
                'required_amount' => $amount,
                'current_amount' => 0,
            ];
        }

        return $tasks;
    }

    /**
     * Check if this task already exists
     *
     * @param array $tasks
     * @param string $key
     * @param integer $id
     * @return boolean
     */
    private function hasTaskAlready(array $tasks, string $key, int $id): bool
    {
        foreach ($tasks as $task) {
            if ($task[$key] === $id) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get item for the crafting task.
     *
     * @param string $type
     * @param string $gamMapName
     * @return Item
     * @throws Exception
     */
    private function getItemForCraftingTask(string $type, string $gamMapName): Item
    {

        $gameMapValue = new MapNameValue($gamMapName);

        $item = Item::inRandomOrder()->doesntHave('itemSuffix')
            ->doesntHave('itemPrefix')
            ->whereNotIn('type', ['quest', 'alchemy', 'trinket', 'artifact'])
            ->whereNull('specialty_type');

        if ($gameMapValue->isSurface() || $gameMapValue->isTheIcePlane() || $gameMapValue->isDelusionalMemories()) {
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

        if ($gameMapValue->isPurgatory()) {
            $item->where('skill_level_required', '<=', 350);
        }

        if ($gameMapValue->isTwistedMemories()) {
            $item->where('skill_level_required', '<=', 370);
        }

        return $item->where('crafting_type', $type)->first();
    }
}
