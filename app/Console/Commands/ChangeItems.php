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
use Illuminate\Support\Facades\DB;

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
                                     ->whereNull('item_suffix_id')
                                     ->whereNull('item_prefix_id')->first();

                if (!is_null($foundBaseItem)) {

                    $oldCost = $item->cost;

                    $item->update([
                        'cost' => $foundBaseItem->cost,
                    ]);

                    $item = $item->refresh();
                    $this->newLine(1);
                    $this->line($item->name . ' Previous Base Cost: ' . $oldCost . ' New Cost: ' . $item->cost);

                } else {
                    $this->newLine(1);
                    $this->line($item->name . ' Base cost is the same');
                }

                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);
            $this->line('Updating items and item affixes that can drop.');

            DB::table('items')->where('cost', '<=', 5000)->update(['can_drop' => true]);
            DB::table('item_affixes')->where('cost', '<=', 5000)->update(['can_drop' => true]);

            $this->newLine(2);
            $this->line('All done.');
        } else {
            $this->error('How are their not items with affixes?');
        }

    }
}
