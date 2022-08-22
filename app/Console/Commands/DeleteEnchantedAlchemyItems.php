<?php

namespace App\Console\Commands;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Models\MarketBoard;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;

class DeleteEnchantedAlchemyItems extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'delete:enchanted-alchemy-items';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deletes the alchemy items that have affixes on them.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void {

        $alchemyPrefixIds = Item::where('type', 'alchemy')
                                ->whereNotNull('item_prefix_id')
                                ->pluck('id')
                                ->toArray();

        $alchemySuffixIds = Item::where('type', 'alchemy')
                                ->whereNotNull('item_suffix_id')
                                ->pluck('id')
                                ->toArray();


        $progressBar = new ProgressBar(new ConsoleOutput(), Character::count());

        Character::chunkById(100, function($characters) use ($alchemyPrefixIds, $alchemySuffixIds, $progressBar) {
           foreach ($characters as $character) {
               $character->inventory->slots()->whereIn('item_id', $alchemyPrefixIds)->delete();
               $character->inventory->slots()->whereIn('item_id', $alchemySuffixIds)->delete();

               $progressBar->advance();
           }
        });

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        MarketBoard::whereIn('item_id', $alchemyPrefixIds)->delete();
        MarketBoard::whereIn('item_id', $alchemySuffixIds)->delete();

        Item::whereIn('id', $alchemyPrefixIds)->delete();
        Item::whereIn('id', $alchemySuffixIds)->delete();

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $progressBar->finish();
    }
}
