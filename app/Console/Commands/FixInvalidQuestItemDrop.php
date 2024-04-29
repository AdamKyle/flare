<?php

namespace App\Console\Commands;

use App\Flare\Models\Character;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\QuestsCompleted;
use Illuminate\Console\Command;

class FixInvalidQuestItemDrop extends Command
{
    protected $signature = 'fix:invalid-quest-item-drop';

    protected $description = 'Searches for invalid quest items and replaces them with proper quest items.';

    public function handle()
    {

        // Process items in batches
        Item::where('type', 'quest')->where(function($query) {
            $query->whereNotNull('item_prefix_id')
                  ->whereNotNull('item_suffix_id');
        })->chunk(100, function ($items) {
            foreach ($items as $item) {
                $this->processItem($item);
            }
        });

        // Delete the items.
        Item::where('type', 'quest')->where(function($query) {
            $query->whereNotNull('item_prefix_id')
                  ->whereNotNull('item_suffix_id');
        })->delete();
    }

    private function processItem($item)
    {
        // Find proper quest item
        $properQuestItem = Item::where('type', 'quest')->where(function($query) {
            $query->whereNull('item_prefix_id')
                  ->whereNull('item_suffix_id');
        })->first();

        if (is_null($properQuestItem)) {
            return;
        }

        InventorySlot::where('item_id', $item->id)->chunkById(100, function($inventorySlots) use ($item, $properQuestItem) {
            foreach ($inventorySlots as $slot) {
                $this->handleCharacter($slot->inventory->character, $item, $properQuestItem);
            }
        });
    }

    private function handleCharacter($character, $item, $properQuestItem)
    {
        // Check if the character has the proper item in inventory or completed quest
        if ($this->characterHasProperItem($character, $properQuestItem)) {
            InventorySlot::where('inventory_id', $character->inventory->id)
                         ->where('item_id', $item->id)
                         ->delete();

            return;
        }

        // Update inventory slots with the proper quest item
        InventorySlot::where('inventory_id', $character->inventory->id)
            ->where('item_id', $item->id)
            ->update(['item_id' => $properQuestItem->id]);
    }

    private function characterHasProperItem($character, $properQuestItem)
    {
        return InventorySlot::where('inventory_id', $character->inventory->id)
                ->where('item_id', $properQuestItem->id)
                ->exists() || QuestsCompleted::where('character_id', $character->id)
                ->whereHas('quest', function ($query) use ($properQuestItem) {
                    $query->where('item_id', $properQuestItem->id)
                        ->orWhere('secondary_required_item', $properQuestItem->id);
                })
                ->exists();
    }
}
