<?php

namespace App\Console\Commands;

use App\Flare\Models\CharacterBoon;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\MarketBoard;
use App\Flare\Models\MarketHistory;
use Illuminate\Console\Command;

class RemoveDuplicateAlchemyItems extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remove:invalid-alchemy-items';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Removes Invalid Alchemy items';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void {
        $invalidNames = [
            'Enchanters Brew',
            'Enchantress Potion',
            'Alchemists Eye',
            'Alchemists Drink',
            'Alchemists Dust',
            'Crafters Brew',
            'Crafters Insight',
            'Astral Mages Ink',
            'Time Mages Bomb',
            'Deaths Cloud',
            'Battle Mages Sand',
        ];

        $namesToChange = [
            "Battlemage's Sand" => "Battle Mage's Sand"
        ];

        $this->line('Changing Names: ');
        $this->newLine();

        foreach ($namesToChange as $oldName => $newName) {
            $alchemyItem = Item::where('name', $oldName)->first();

            if (is_null($alchemyItem)) {
                $this->line('No alchemy item found for: ' . $oldName);

                continue;
            }

            $alchemyItem->update([
                'name' => $newName
            ]);

            $this->line('Changed name from: ' . $oldName . ' to: ' . $newName);
        }

        $this->newLine();
        $this->line('Finished name change. Now deleting ...');
        $this->line('Starting deletion of invalid alchemy items');
        $this->newLine();

        foreach ($invalidNames as $name) {
            $alchemyItem = Item::where('name', $name)->first();

            if (is_null($alchemyItem)) {
                $this->line('No alchemy item found for: ' . $name);

                continue;
            }

            MarketBoard::where('item_id', $alchemyItem->id)->delete();
            MarketHistory::where('item_id', $alchemyItem->id)->delete();
            CharacterBoon::where('item_id', $alchemyItem->id)->delete();
            InventorySlot::where('item_id', $alchemyItem->id)->delete();

            $alchemyItem->delete();

            $this->line('Delete: ' . $name);
        }

        $this->newLine();

        $this->line('All done.');
    }
}
