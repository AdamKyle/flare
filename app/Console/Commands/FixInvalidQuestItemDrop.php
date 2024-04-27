<?php

namespace App\Console\Commands;

use App\Flare\Models\Character;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\QuestsCompleted;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

class FixInvalidQuestItemDrop extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:invalid-quest-item-drop';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Searches for invalid quest items and replaces them with proper quest items.';

    /**
     * Execute the console command.
     */
    public function handle() {
        $items = Item::where('type', 'quest')
            ->where(function ($query) {
                $query->whereNotNull('item_suffix_id')
                    ->orWhereNotNull('item_prefix_id');
            })->get();

        Character::chunkById(150, function($characters) use ($items) {
            foreach ($characters as $character) {

                if (is_null($character->inventory)) {
                    continue;
                }

                $this->handleUpdatingQuestItems($items, $character);
            }
        });

        Item::whereIn('id', $items->pluck('id')->toArray())->delete();
    }

    private function handleUpdatingQuestItems(Collection $items, Character $character): void {
        foreach ($items as $item) {
            $properQuestItem = Item::where('name', $item->name)
                ->whereNull('item_prefix_id')
                ->whereNull('item_suffix_id')
                ->where('type', $item->type)
                ->first();

            $foundItem = InventorySlot::where('inventory_id', $character->inventory->id)->where('item_id', $properQuestItem->id)->first();
            $foundCompletedQuest = QuestsCompleted::where('character_id', $character->id)
                ->where(function($query) use ($properQuestItem) {
                    $query->whereHas('quest', function($subQuery) use ($properQuestItem) {
                        $subQuery->where('item_id', $properQuestItem->id)
                            ->orWhere('secondary_required_item', $properQuestItem->id);
                    });
                })
                ->first();

            if (!is_null($foundCompletedQuest)) {
                continue;
            }

            if (!is_null($foundItem)) {
                continue;
            }

            InventorySlot::where('item_id', $item->id)->update([
                'item_id' => $properQuestItem->id,
            ]);
        }
    }
}
