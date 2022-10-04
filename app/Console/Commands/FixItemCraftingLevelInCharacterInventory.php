<?php

namespace App\Console\Commands;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use Illuminate\Console\Command;

class FixItemCraftingLevelInCharacterInventory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'validate:crafting-levels';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Validate and fix crafting levels on items';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void {
        Character::chunkById(100, function($characters) {
            foreach ($characters as $character) {
                $this->handleInventory($character);
                $this->handleInventorySlots($character);
            }
        });
    }

    protected function handleInventory(Character $character) {
        $slots = $character->inventory->slots;

        foreach ($slots as $slot) {
            if ($slot->item->type === 'alchemy' || $slot->item->type === 'quest') {
                continue;
            }


            $slotItem = $slot->item;
            $item     = Item::where('name', $slotItem->name)->first();

            if ($slotItem->skill_level_required !== $item->skill_level_required) {
                $slotItem->update([
                    'skill_level_required' => $item->skill_level_required,
                    'skill_level_trivial'  => $item->skill_level_trivial,
                ]);
            }
        }
    }

    protected function handleInventorySlots(Character $character) {
        $inventorySets = $character->inventorySets;

        foreach ($inventorySets as $set) {
            foreach ($set->slots as $slot) {
                $slotItem = $slot->item;
                $item     = Item::where('name', $slotItem->name)->first();

                if ($slotItem->skill_level_required !== $item->skill_level_required) {
                    $slotItem->update([
                        'skill_level_required' => $item->skill_level_required,
                        'skill_level_trivial'  => $item->skill_level_trivial,
                    ]);
                }
            }
        }
    }
}
