<?php

namespace App\Console\Commands;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use Illuminate\Console\Command;

class FixDuplicateQuestItems extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:duplicate-quest-items';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Removes Duplicate Quest Items';

    /**
     * Execute the console command.
     */
    public function handle() {
        $characters = Character::with('inventory.slots.item')
            ->whereHas('inventory.slots.item', function ($query) {
                $query->where('type', 'quest');
            })
            ->get();

        $duplicateQuestItems = [];

        foreach ($characters as $character) {
            $questItems = $character->inventory->slots
                ->where('item.type', 'quest')
                ->pluck('item.name')
                ->toArray();

            $duplicateItems = array_diff_assoc($questItems, array_unique($questItems));

            if (!empty($duplicateItems)) {
                $duplicateQuestItems[] = [
                    'character_name' => $character->name,
                    'duplicate_quest_item_names' => $duplicateItems
                ];
            }
        }

        if (empty($duplicateQuestItems)) {
            return;
        }

        foreach ($duplicateQuestItems as $duplicateItemDetails) {
            $character = Character::where('name', $duplicateItemDetails['character_name'])->first();

            if (is_null($character)) {
                $this->line('No Character for name: ' . $duplicateItemDetails['character_name']);

                continue;
            }

            $itemIds = Item::whereIn('name', $duplicateItemDetails['duplicate_quest_item_names'])->pluck('id')->toArray();

            $character->inventory->slots()->whereIn('item_id', $itemIds)->delete();

            $this->line('Deleted Duplicate Quest Items for: ' . $character->name);
        }
    }
}
