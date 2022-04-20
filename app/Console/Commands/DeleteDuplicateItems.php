<?php

namespace App\Console\Commands;

use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
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
        $duplicateIDs = Item::where('name', 'like', '%DUPLICATED%')->get()->pluck('id')->toArray();

        foreach ($duplicateIDs as $duplicateID) {
            $inventorySlotsWithItem    = InventorySlot::where('item_id', $duplicateID)->get();
            $inventorySetSlotsWithItem = SetSlot::where('item_id', $duplicateID)->get();

            if ($inventorySetSlotsWithItem->isEmpty() && $inventorySlotsWithItem->isEmpty()) {
                Item::find($duplicateID)->delete();

                $this->info('Deleted ' . $duplicateID);
                $this->newLine();
            } else {
                $duplicatedItem = Item::find($duplicateID);

                $name = $duplicatedItem->name;

                $name = trim(str_replace('DUPLICATED', '', $name));

                foreach ($inventorySlotsWithItem as $slot) {
                   $this->replaceItem($slot, $name);
                }

                foreach ($inventorySetSlotsWithItem as $slot) {
                    $this->replaceItem($slot, $name);
                }

                Item::find($duplicateID)->delete();

                $this->info('Deleted (and replaced with non duplicate items) ' . $duplicateID);
                $this->newLine();
            }
        }
    }

    protected function replaceItem(InventorySlot | SetSlot $slot, string $name) {
        $item = $slot->item;

        $newItem = Item::where('name', $name)->first();

        if ($item->affix_count > 0) {
            $newItemWithAffixes = Item::where('item_suffix_id', $item->item_suffix_id)->where('item_prefix_id', $item->item_prefix_id)->where('name', $name)->first();

            if (is_null($newItemWithAffixes)) {

                $duplicatedItem = $newItem->duplicate();

                $duplicatedItem->update([
                    'item_suffix_id' => $item->item_suffix_id,
                    'item_prefix_id' => $item->item_prefix_id
                ]);

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
}
