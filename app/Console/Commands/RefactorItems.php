<?php

namespace App\Console\Commands;

use App\Admin\Services\ItemsService;
use App\Flare\Models\Item;
use App\Flare\Models\MarketHistory;
use App\Flare\Models\Notification;
use Carbon\Carbon;
use Illuminate\Console\Command;

class RefactorItems extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'refactor:items';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Adds missing data to items.';

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
     * @return mixed
     */
    public function handle(ItemsService $itemsService)
    {
        $foundItem = Item::where('name', 'Spell Woven Sleeves')
            ->whereNull('item_prefix_id')
            ->whereNull('item_suffix_id')
            ->whereNull('agi_mod')
            ->whereNull('focus_mod')
            ->first();

        if (!is_null($foundItem)) {
            $itemsService->deleteItem($foundItem);

            $this->line('Found and deleted your item ...');
        }

        Item::whereNotNull('item_prefix_id')->chunkById(100, function($items) {
           foreach ($items as $item) {
               $foundItem = Item::where('name', $item->name)
                   ->whereNull('item_prefix_id')
                   ->whereNull('item_suffix_id')
                   ->first();

               if (is_null($foundItem)) {
                   $this->line($item->name . '(id: '.$item->id.') does not have a parent?');
               }

               $attributes = $foundItem->getAttributes();

               unset($attributes['id']);
               unset($attributes['item_prefix_id']);
               unset($attributes['item_suffix_id']);
               unset($attributes['created_at']);
               unset($attributes['updated_at']);

               $item->update($attributes);

               $this->line($item->affix_name . ' was updated with new stats.');

           }
        });

        Item::whereNotNull('item_suffix_id')->chunkById(100, function($items) {
            foreach ($items as $item) {
                $foundItem = Item::where('name', $item->name)
                    ->whereNull('item_prefix_id')
                    ->whereNull('item_suffix_id')
                    ->first();

                if (is_null($foundItem)) {
                    $this->line($item->name . '(id: '.$item->id.') does not have a parent?');
                }

                $attributes = $foundItem->getAttributes();

                unset($attributes['id']);
                unset($attributes['item_prefix_id']);
                unset($attributes['item_suffix_id']);
                unset($attributes['created_at']);
                unset($attributes['updated_at']);

                $item->update($attributes);

                $this->line($item->affix_name . ' was updated with new stats.');
            }
        });
    }
}
