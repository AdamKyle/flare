<?php

namespace App\Console\Commands;

use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\MarketBoard;
use App\Flare\Models\SetSlot;
use Illuminate\Console\Command;

class FilterOutDuplicateItems extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'filter:duplicate-items';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Removes Duplicate items';

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
        $items = Item::whereNull('item_prefix_id')
                     ->whereNull('item_suffix_id')
                     ->whereNotIn('type', ['quest', 'alchemy'])
                     ->get();

        $itemsToBeRemoved = [];

        foreach ($items as $item) {
            $matchingItems = $this->getIdsOfSimmilarItems($item);

            if (!empty($matchingItems)) {
                if (count($matchingItems) > 1 && !isset($itemsToBeRemoved[$item->name])) {
                    $itemsToBeRemoved[$item->name] = $matchingItems;
                }
            }
        }

        if (!empty($itemsToBeRemoved)) {

            foreach ($itemsToBeRemoved as $itemName => $ids) {
                array_shift($ids); // remove the first one and keep it.

                $inventorySetsWithTheItems = SetSlot::whereIn('item_id', $ids)->get();
                $inventoryWithItems = InventorySlot::whereIn('item_id', $ids)->get();

                if ($inventorySetsWithTheItems->isNotEmpty()) {
                    foreach ($inventorySetsWithTheItems as $inventorySetsWithTheItem) {
                        $inventorySetsWithTheItems->delete();
                    }
                }

                if ($inventoryWithItems->isNotEmpty()) {
                    foreach ($inventoryWithItems as $inventoryWithItem) {
                        $inventoryWithItem->delete();
                    }
                }


                Item::whereIn('id', $ids)->delete();

                $this->line('One of: ' . $itemName . ' Has been deleted.');
            }
        }
    }

    protected function getIdsOfSimmilarItems(Item $item): array {
        return Item::whereNull('item_prefix_id')
            ->whereNull('item_suffix_id')
            ->whereNotIn('type', ['quest', 'alchemy'])
            ->where('name', $item->name)
            ->pluck('id')->toArray();
    }
}
