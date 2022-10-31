<?php

namespace App\Console\Commands;

use App\Flare\Models\Character;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use Illuminate\Console\Command;

class RemoveInvalidQuestItems extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'delete:invalid-quest-names';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove invalid Quest Items';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void {
        $invalidQuestItems = [
            'Satans Hide',
            'Smithies Dying Ember',
            'Smithies Hammer',
            'Broken Smithies Anvil',
            'Lifes Flail',
            'Weapon Smiths Book',
            'Weapon Smiths Master Book',
            'Blacksmiths Book',
            'Black Smiths Master Recipes',
            'Kings Ring',
            'Ring Crafters Master Book',
            'Dead Kings Crown',
            'Dead Fiends Hide',
            'Kings Book of Hope',
            'Kings Scepter',
            'Mages Tome',
            'Goblins Quickening Rune',
            'Purgatories Lantern',
            'Enchanted Candle Stick of Light',
            'Purgatories Cursed Candle',
            'The Wizards Enchantment',
        ];

        $rename = [
            'Ring Crafters Book' => "Ring Crafter's Book",
        ];

        foreach ($rename as $oldName => $newname) {
            $questItem = Item::where('name', $oldName)->first();

            if (is_null($questItem)) {
                continue;
            }

            $questItem->update([
                'name' => $newname
            ]);

            $this->line('Updated: ' . $oldName . ' to: ' . $newname);
        }

        foreach ($invalidQuestItems as $questItemName) {
            $questItem = Item::where('name', $questItemName)->first();

            if (is_null($questItem)) {
                continue;
            }

            InventorySlot::where('item_id', $questItem->id)->delete();
            $questItem->delete();

            $this->line('Deleted: ' . $questItemName);
        }
    }
}
