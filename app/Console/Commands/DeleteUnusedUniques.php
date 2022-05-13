<?php

namespace App\Console\Commands;

use App\Flare\Models\HolyStack;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\ItemAffix;
use App\Flare\Models\MarketBoard;
use App\Flare\Models\MarketHistory;
use App\Flare\Models\SetSlot;
use Illuminate\Console\Command;

class DeleteUnusedUniques extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'delete:unused-uniques';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deletes all unused Uniques';

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
        forEach(ItemAffix::where('randomly_generated', true)->get() as $affix) {
            $this->processUniqueItems($affix);
        }
    }

    public function processUniqueItems(ItemAffix $itemAffix) {
        $suffixes = Item::where('item_suffix_id', $itemAffix->id)->get();
        $prefixes = Item::where('item_prefix_id', $itemAffix->id)->get();

        foreach ($suffixes as $suffixItem) {
            $this->deleteItem($suffixItem);
        }

        foreach ($prefixes as $prefixItem) {
            $this->deleteItem($prefixItem);
        }
    }

    public function deleteItem(Item $item) {
        $notInInventory = InventorySlot::where('item_id', $item->id)->get()->isEmpty();
        $notInSets      = SetSlot::where('item_id', $item->id)->get()->isEmpty();

        if ($notInInventory && $notInSets) {
            HolyStack::where('item_id', $item->id)->delete();
            MarketBoard::where('item_id', $item->id)->delete();
            MarketHistory::where('item_id', $item->id)->delete();

            $this->line('Deleted: ' . $item->affix_name);

            $item->delete();
        } else {
            $this->line('Cannot delete item, character has item ... ('.$item->affix_name.')');
        }
    }
}
