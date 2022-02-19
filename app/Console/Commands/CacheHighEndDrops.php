<?php

namespace App\Console\Commands;

use App\Flare\Models\Item;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * @codeCoverageIgnore
 */
class CacheHighEndDrops extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:high-end-items';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Caches a list of high end items that can drop for the player in various special locations.';

    protected $itemIds = [];

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

        $prefixItems = Item::inRandomOrder()->where('cost', '<=', 4000000000)->whereHas('itemPrefix')->take(100)->pluck('id')->toArray();
        $suffixItems = Item::inRandomOrder()->where('cost', '<=', 4000000000)->whereHas('itemSuffix')->take(100)->pluck('id')->toArray();

        $this->itemIds = array_merge($suffixItems, $prefixItems);

        if (!empty($this->itemIds) && count($this->itemIds) <= 100) {
            Cache::put('highend-droppable-items', $this->itemIds);
        }

        if (!empty($this->itemIds)) {
            $randomItems = [];

            while (count($randomItems) < 100) {
                $randomIndex = mt_rand(0, (count($this->itemIds) - 1));
                $randomId    = $this->itemIds[$randomIndex];

                if (!in_array($randomId, $randomItems)) {
                    $randomItems[] = $this->itemIds[$randomIndex];
                }
            }

            Cache::put('highend-droppable-items', $randomItems);
        }
    }
}
