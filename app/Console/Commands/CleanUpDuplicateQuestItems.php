<?php

namespace App\Console\Commands;

use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\Quest;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

class CleanUpDuplicateQuestItems extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clean-up:duplicate-quest-items';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up duplicate quest items';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void {
        $items = Item::where('type', 'quest')->get();

        foreach ($items as $item) {
            $duplicates = $this->getDuplicateItems($item);

            if (!is_null($duplicates)) {
                $this->handleDuplicateItems($duplicates);
            }
        }

        // items to replace and delete.
        // to replace/delete => new item.
        $questItemsToDelete = [
            'Satans Heart'                    => 'Satan\'s Heart',
            'Satans Mask'                     => 'Satan\'s Mask',
            'Mages Teleport Scroll'           => 'Mage\'s Teleport Scroll',
            'Magicians Enchanted Copper Coin' => 'Magician\'s Enchanted Copper Coin',
            'Enchanters Book'                 => 'Enchanter\'s Book',
            'Disenchanters Magnifying Glass'  => 'Disenchanter\'s Magnifying Glass',
            'Bishops Enchanted Scroll'        => 'Bishop\'s Enchanted Scroll',
        ];

        foreach ($questItemsToDelete as $oldItemName => $newItemName) {
            $oldQuestItem = Item::where('name', $oldItemName)->where('type', 'quest')->first();
            $newQuestItem = Item::where('name', $newItemName)->where('type', 'quest')->first();

            if (is_null($oldQuestItem)) {
                $this->error($oldItemName . ' not found.');

                return;
            }

            if (is_null($newQuestItem)) {
                $this->error($newItemName . ' not found.');

                return;
            }

            $oldQuestItemId = $oldQuestItem->id;
            $newQuestItemId = $newQuestItem->id;

            $quests = Quest::where('item_id', $oldQuestItemId)
                           ->orWhere('secondary_required_item', $oldQuestItemId)
                           ->get();

            foreach ($quests as $quest) {
                if ($quest->item_id == $oldQuestItemId) {
                    $quest->item_id = $newQuestItemId;

                    $this->line('Updated primary required item Id for quest: ' . $quest->name);
                }

                if ($quest->secondary_required_item == $oldQuestItemId) {
                    $quest->secondary_required_item = $newQuestItemId;

                    $this->line('Updated secondary required item Id for quest: ' . $quest->name);
                }

                $quest->save();
            }

            InventorySlot::where('item_id', $oldQuestItemId)->update(['item_id' => $newQuestItemId]);

            $this->line('Possibly updated characters inventory to use the new quest item: ' . $newQuestItem->name);


            $oldQuestItem->delete();

            $this->line('Deleted old quest item: ' . $oldItemName);
        }

        $this->line('Quest items have been cleaned.');
    }

    /**
     * Find duplicate items or return null.
     *
     * @param Item $item
     * @return Collection|null
     */
    protected function getDuplicateItems(Item $item): ?Collection {
        $items = Item::where('name', $item->name)->orderBy('id', 'asc')->get();

        if ($items->count() > 1) {
            return $items;
        }

        return null;
    }

    /**
     * Handle duplicate items.
     *
     * - Keep one of the items
     * - Replace the other items with the item we keep and delete the other items.
     *
     * @param Collection $duplicates
     * @return void
     */
    protected function handleDuplicateItems(Collection $duplicates): void {

        // keep the first item, to swap with characters who have the other items.
        $questItemToKeep = $duplicates->shift();

        foreach ($duplicates as $duplicate) {
            InventorySlot::where('item_id', $duplicate->id)->update([
                'item_id' => $questItemToKeep->id,
            ]);

            $this->line('Replaced item (if needed): ' . $duplicate->name . '. Deleted duplicate item.');

            $duplicate->delete();
        }
    }
}
