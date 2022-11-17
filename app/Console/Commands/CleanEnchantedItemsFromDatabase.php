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

        ini_set('memory_limit', '3G');

        $prefixItems = Item::whereHas('itemPrefix')
            ->whereDoesntHave('inventorySlots')
            ->whereDoesntHave('inventorySetSlots')
            ->whereDoesntHave('marketListings')
            ->whereDoesntHave('marketHistory')
            ->get();

        foreach ($prefixItems as $item) {
            if ($item->appliedHolyStacks()->count() > 0) {
                $item->appliedHolyStacks()->delete();
            }

            $item->delete();
        }

        $suffixItems = Item::whereHas('itemSuffix')
            ->whereDoesntHave('inventorySlots')
            ->whereDoesntHave('inventorySetSlots')
            ->whereDoesnthave('marketListings')
            ->whereDoesntHave('marketHistory')
            ->get();

        foreach ($suffixItems as $item) {
            if ($item->appliedHolyStacks()->count() > 0) {
                $item->appliedHolyStacks()->delete();
            }

            $item->delete();
        }
    }
}
