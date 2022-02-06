<?php

namespace App\Console\Commands;

use App\Flare\Models\Item;
use Illuminate\Console\Command;

class CleanEnchantedItemsFromDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clean:enchanted-items';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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

       Item::whereHas('itemPrefix')
           ->whereDoesntHave('inventorySlots')
           ->whereDoesntHave('inventorySetSlots')
           ->whereDoesntHave('marketListings')
           ->whereDoesntHave('marketHistory')
           ->delete();

       Item::whereHas('itemSuffix')
           ->whereDoesntHave('inventorySlots')
           ->whereDoesntHave('inventorySetSlots')
           ->whereDoesnthave('marketListings')
           ->whereDoesntHave('marketHistory')
           ->delete();
    }
}
