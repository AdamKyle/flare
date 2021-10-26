<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Flare\Models\Item;
use App\Flare\Models\ItemAffix;

/**
 * @codeCoverageIgnore
 */
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
    public function handle()
    {
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

               $attributes['market_sellable'] = true;

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

                $attributes['market_sellable'] = true;

                $item->update($attributes);

                $this->line($item->affix_name . ' was updated with new stats.');
            }
        });

        Item::where('cost', '<=', 1000)->update(['can_drop' => true]);
        Item::where('cost', '>=', 1000)->update(['can_drop' => false]);

        ItemAffix::where('cost', '<=', 1500)->update(['can_drop' => true]);
        ItemAffix::where('cost', '>=', 1500)->update(['can_drop' => false]);

        $this->line('');
        $this->line('Stats: ');
        $this->line('========');
        $this->line('there is: '. Item::whereNotNull('item_prefix_id')->whereNotNull('item_suffix_id')->count() . ' Items with both prefixes and suffixes.');
        $this->line('there is: '. Item::whereNotNull('item_prefix_id')->whereNull('item_suffix_id')->count() . ' Items with one prefix and no suffixes.');
        $this->line('there is: '. Item::whereNull('item_prefix_id')->whereNotNull('item_suffix_id')->count() . ' Items with no prefixes and one suffix.');
    }
}
