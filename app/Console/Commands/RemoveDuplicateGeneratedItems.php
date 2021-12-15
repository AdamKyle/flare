<?php

namespace App\Console\Commands;

use App\Flare\Models\InventorySet;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\SetSlot;
use Illuminate\Console\Command;

class RemoveDuplicateGeneratedItems extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remove:duplicate-generated-items';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Removes duplicate base items.';

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
        Item::doesntHave('itemPrefix')->doesntHave('itemSuffix')->doesntHave('children')->whereNotIn('type', ['alchemy', 'quest'])->where('id', '>', 1000)->chunkById(100, function($items) {
            foreach ($items as $item) {
                if ($item->children->isEmpty()) {
                    $inventorySlot = InventorySlot::where('item_id', $item->id)->first();
                    $setSlot       = SetSlot::where('item_id', $item->id)->first();

                    if (is_null($inventorySlot) && is_null($setSlot)) {
                        $this->line('Deleted: ' . $item->name);
                        $item->delete();
                    }
                }
            }
        });
    }
}
