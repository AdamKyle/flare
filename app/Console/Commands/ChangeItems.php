<?php

namespace App\Console\Commands;

use App\Admin\Services\ItemAffixService;
use App\Flare\Events\ServerMessageEvent;
use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Models\Character;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\ItemAffix;
use App\Flare\Models\MarketBoard;
use App\Flare\Models\MarketHistory;
use App\Flare\Models\Skill;
use Facades\App\Flare\Calculators\SellItemCalculator;
use Illuminate\Console\Command;

class ChangeItems extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:item-cost';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Change the item cost for items with atached affixes.';

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
        $items = Item::whereNotNull('item_prefix_id')->orWhereNotNull('item_suffix_id')->get();


        if (!$items->isEmpty()) {
            $bar = $this->output->createProgressBar(count($items));

            $bar->start();

            foreach ($items as $item) {
                $foundBaseItem = Item::where('name', $item->name)
                                     ->where('cost', '!=', $item->cost)
                                     ->whereIsNull('item_suffix_id')
                                     ->whereisNull('item_affix_id')
                                     ->first();

                if (!is_null($foundBaseItem)) {
                    $item->update([
                        'cost' => $foundBaseItem->cost,
                    ]);

                } else {
                    $this->newLine($item->name . ' does not have a base item.');
                }

                $bar->advance();
            }

            $bar->finish();

            $this->newLine('All done.');
        } else {
            $this->error('How are their not items with affixes?');
        }

    }
}
