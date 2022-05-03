<?php

namespace App\Console\Commands;

use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\MarketBoard;
use App\Flare\Models\MarketHistory;
use App\Flare\Models\SetSlot;
use Illuminate\Console\Command;

class DeleteDuplicateItems extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'delete:duplicate-items';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'deletes duplicated items';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $duplicateIDs = Item::where('name', 'like', '%DUPLICATE%')->get()->pluck('id')->toArray();

        foreach ($duplicateIDs as $duplicateID) {
            $inventorySlotsWithItem    = InventorySlot::where('item_id', $duplicateID)->get();
            $inventorySetSlotsWithItem = SetSlot::where('item_id', $duplicateID)->get();

            if ($inventorySetSlotsWithItem->isEmpty() && $inventorySlotsWithItem->isEmpty()) {

                $duplicateItem = Item::find($duplicateID);

                $this->replaceMarketItems($duplicateItem);

                $this->replaceMarketHistory($duplicateItem);

                $duplicateItem->appliedHolyStacks()->delete();

                $duplicateItem->delete();

                $this->info('Deleted ' . $duplicateID);
                $this->newLine();
            } else {
                $duplicatedItem = Item::find($duplicateID);

                foreach ($inventorySlotsWithItem as $slot) {
                   $this->replaceItem($slot, $duplicatedItem);
                }

                foreach ($inventorySetSlotsWithItem as $slot) {
                    $this->replaceItem($slot, $duplicatedItem);
                }

                $this->replaceMarketItems($duplicatedItem);

                $this->replaceMarketHistory($duplicatedItem);

                $duplicatedItem->appliedHolyStacks()->delete();

                $duplicatedItem->delete();

                $this->info('Deleted (and replaced with non duplicate items) ' . $duplicateID);
                $this->newLine();
            }
        }
    }

    protected function replaceMarketItems(Item $item) {
        $marketListings = MarketBoard::where('item_id', $item->id)->get();
        $name           = trim(str_replace('DUPLICATE', '', $item->name));

        foreach ($marketListings as $listing) {
            $newItemWithAffixes = Item::where('item_suffix_id', $item->item_suffix_id)->where('item_prefix_id', $item->item_prefix_id)->where('name', $name)->first();

            if (is_null($newItemWithAffixes)) {
                $duplicatedItem = Item::where('name', $name)->first()->duplicate();

                $duplicatedItem->update([
                    'item_suffix_id' => $item->item_suffix_id,
                    'item_prefix_id' => $item->item_prefix_id
                ]);

                $duplicatedItem = $this->updateNewItemsHolyStacks($item, $duplicatedItem);

                $itemForMarket = $duplicatedItem->refresh();

                $listing->update([
                    'item_id' => $itemForMarket->id,
                ]);

            } else {
                $listing->update([
                    'item_id' => $newItemWithAffixes->id,
                ]);
            }
        }
    }

    protected function replaceMarketHistory(Item $item) {
        $marketHistories = MarketHistory::where('item_id', $item->id)->get();
        $name            = trim(str_replace('DUPLICATE', '', $item->name));

        foreach ($marketHistories as $history) {
            $newItemWithAffixes = Item::where('item_suffix_id', $item->item_suffix_id)->where('item_prefix_id', $item->item_prefix_id)->where('name', $name)->first();

            if (is_null($newItemWithAffixes)) {
                $duplicatedItem = Item::where('name', $name)->first()->duplicate();

                $duplicatedItem->update([
                    'item_suffix_id' => $item->item_suffix_id,
                    'item_prefix_id' => $item->item_prefix_id
                ]);

                $duplicatedItem = $this->updateNewItemsHolyStacks($item, $duplicatedItem);

                $itemForMarket = $duplicatedItem->refresh();

                $history->update([
                    'item_id' => $itemForMarket->id,
                ]);

            } else {
                $history->update([
                    'item_id' => $newItemWithAffixes->id,
                ]);
            }
        }
    }

    protected function replaceItem(InventorySlot | SetSlot $slot, Item $duplicateItem, bool $hasHolyStacks = false) {
        $item    = $slot->item;

        $name    = trim(str_replace('DUPLICATE', '', $item->name));

        $newItem = Item::where('name', $name)->first();

        if ($item->affix_count > 0) {
            $newItemWithAffixes = Item::where('item_suffix_id', $item->item_suffix_id)->where('item_prefix_id', $item->item_prefix_id)->where('name', $name)->first();

            if (is_null($newItemWithAffixes)) {

                $duplicatedItem = $newItem->duplicate();

                $duplicatedItem->update([
                    'item_suffix_id' => $item->item_suffix_id,
                    'item_prefix_id' => $item->item_prefix_id
                ]);

                $duplicatedItem = $this->updateNewItemsHolyStacks($duplicateItem, $duplicatedItem);

                $itemForSlot = $duplicatedItem->refresh();

                $slot->update([
                    'item_id' =>$itemForSlot->id,
                ]);
            } else {
                $slot->update([
                    'item_id' =>$newItemWithAffixes->id,
                ]);
            }
        } else {
            $slot->update([
                'item_id' => $newItem->id,
            ]);
        }
    }

    protected function updateNewItemsHolyStacks(Item $duplicatedItem, Item $newItem): Item {

        if ($duplicatedItem->appliedHolyStacks()->count() > 0) {
            foreach ($duplicatedItem->appliedHolyStacks as $stack) {
                $stackAttributes = $stack->getAttributes();

                $stackAttributes['item_id'] = $newItem->id;

                $newItem->appliedHolyStacks()->create($stackAttributes);

                $stack->delete();
            }
        }

        return $newItem;
    }
}
