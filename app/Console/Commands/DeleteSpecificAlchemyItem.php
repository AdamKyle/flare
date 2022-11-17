<?php

namespace App\Console\Commands;

use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\MarketBoard;
use App\Flare\Models\MarketHistory;
use Illuminate\Console\Command;

class DeleteSpecificAlchemyItem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'delete:alchemy-item {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'deletes alchemy item by name';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void {
        $item = Item::where('name', $this->argument('name'))->first();

        if (is_null($item)) {
            $this->line('No item Found');

            return;
        }

        MarketBoard::where('item_id', $item->id)->delete();
        MarketHistory::where('item_id', $item->id)->delete();
        InventorySlot::where('item_id', $item->id)->delete();

        $item->delete();

        $this->line('Deleted: ' . $this->argument('name'));
    }
}
