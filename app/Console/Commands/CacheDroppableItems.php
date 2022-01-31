<?php

namespace App\Console\Commands;

use App\Flare\Models\Item;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class CacheDroppableItems extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:droppable-items';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Caches a list of items that can drop for the player.';

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

        Item::where('can_drop', true)->chunkById(500, function($items) {
            foreach ($items as $item) {
                if (is_null($item->item_prefix_id) && is_null($item->item_suffix_id)) {
                    continue;
                }

                if (!is_null($item->item_prefix_id)) {
                    if (!$item->itemPrefix->can_drop) {
                        continue;
                    }
                }

                if (!is_null($item->item_suffix_id)) {
                    if (!$item->itemSuffix->can_drop) {
                        continue;
                    }
                }


                $this->itemIds[] = $item->id;
            }
        });

        if (!empty($this->itemIds)) {
            $randomItems = [];

            while (count($randomItems) < 100) {
                $randomIndex = mt_rand(0, (count($this->itemIds) - 1));
                $randomId    = $this->itemIds[$randomIndex];

                if (!in_array($randomId, $randomItems)) {
                    $randomItems[] = $this->itemIds[$randomIndex];
                }
            }

            Cache::put('droppable-items', $randomItems);
        }
    }
}
