<?php

namespace App\Console\AfterDeployment;

use App\Flare\Models\FactionLoyaltyNpcTask;
use App\Flare\Models\Item;
use App\Flare\Values\MapNameValue;
use Illuminate\Console\Command;

class FixFactionLoyaltyCraftingTasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:faction-loyalty-crafting-tasks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fixes invalid items for faction loyalty crafting tasks.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        FactionLoyaltyNpcTask::whereJsonLength('fame_tasks', '>', 0)->chunkById(100, function($factionLoyaltyTasks) {
            foreach ($factionLoyaltyTasks as $factionLoyaltyTask) {
                $this->fixInvalidItemsOnFactionLoyaltyTask($factionLoyaltyTask);
            }
        });
    }

    private function fixInvalidItemsOnFactionLoyaltyTask(FactionLoyaltyNpcTask $factionLoyaltyNpcTask): void {
        $tasks = $factionLoyaltyNpcTask->fame_tasks;

        $types = [
            'weapon',
            'ring',
            'armour',
            'spell',
        ];


        foreach ($tasks as $index => $task) {
            if ($task['type'] === 'bounty') {
                continue;
            }

            if ($task['type'] === 'weapon') {
                $this->info('Found an invalid type: Weapon, replacing ... ');

                if (is_null($factionLoyaltyNpcTask->factionLoyaltyNpc) || is_null($factionLoyaltyNpcTask->factionLoyalty)) {
                    continue;
                }

                $gameMapName = $factionLoyaltyNpcTask->factionLoyaltyNpc->npc->gameMap->name;

                $item = $this->getItemForCraftingTask($types[rand(0, count($types) - 1)], $gameMapName);

                $task['item_id'] = $item->id;
                $task['item_name'] = $item->name;
                $task['type'] = $item->type;

                $tasks[$index] = $task;

                $this->info('Replaced weapon type ');

                continue;
            }

            $doesItemExist = Item::where('id', $task['item_id'])->exists();

            if (!$doesItemExist) {
                $this->info('Item: ' . $task['item_name'] . ' does not exist for id: ' . $task['item_id'] . '. Starting replacement');

                $item = Item::where('name', $task['item_name'])
                    ->doesntHave('itemSuffix')
                    ->doesntHave('itemPrefix')
                    ->doesntHave('appliedHolyStacks')
                    ->doesntHave('sockets')
                    ->whereNotIn('type', ['quest', 'alchemy', 'trinket', 'artifact'])
                    ->whereNull('specialty_type')
                    ->first();

                if (is_null($item)) {

                    if (is_null($factionLoyaltyNpcTask->factionLoyaltyNpc) || is_null($factionLoyaltyNpcTask->factionLoyalty)) {
                        continue;
                    }

                    $gameMapName = $factionLoyaltyNpcTask->factionLoyaltyNpc->npc->gameMap->name;

                    $item = $this->getItemForCraftingTask($types[rand(0, count($types) - 1)], $gameMapName);
                }

                $task['item_id'] = $item->id;
                $task['item_name'] = $item->name;
                $task['type'] = $item->type;

                $tasks[$index] = $task;

                $this->info('Replaced item.');
            }
        }

        $factionLoyaltyNpcTask->update([
            'fame_tasks' => $tasks,
        ]);
    }

    private function getItemForCraftingTask(string $type, string $gamMapName): Item
    {

        $gameMapValue = new MapNameValue($gamMapName);

        $item = Item::inRandomOrder()->doesntHave('itemSuffix')
            ->doesntHave('itemPrefix')
            ->doesntHave('appliedHolyStacks')
            ->doesntHave('sockets')
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
