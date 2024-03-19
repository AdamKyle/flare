<?php

namespace App\Console\Commands;

use App\Flare\Models\Inventory;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\Quest;
use App\Flare\Models\QuestsCompleted;
use App\Flare\Values\ItemEffectsValue;
use Illuminate\Console\Command;

class GiveNewSlotsQuestItem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'give:new-slots-quest-item';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gives the slows quest item to all players who can have it.';

    /**
     * Execute the console command.
     */
    public function handle() {
        $quest = Quest::where('name', 'The truth is out there')->first();

        if (is_null($quest)) {
             $this->error('Quest does not exist');

             return;
        }

        $item = Item::where('effect', ItemEffectsValue::MERCENARY_SLOT_BONUS)->first();

        if (is_null($item)) {
            $this->error('item does not exist');
        }

        $characterIdsWithQuestCompleted = QuestsCompleted::where('quest_id', $quest->id)->pluck('character_id')->toArray();
        $inventoryIds = Inventory::whereIn('character_id', $characterIdsWithQuestCompleted)->pluck('id')->toArray();

        InventorySlot::whereIn('inventory_id', $inventoryIds)->update([
            'item_id' => $item->id
        ]);
    }
}
