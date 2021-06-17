<?php

namespace App\Console\Commands;

use App\Admin\Services\ItemAffixService;
use App\Flare\Events\ServerMessageEvent;
use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\ItemAffix;
use Facades\App\Flare\Calculators\SellItemCalculator;
use Illuminate\Console\Command;

class ChangeItems extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:affixes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix the item affixes.';

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
    public function handle(ItemAffixService $itemAffixService)
    {

        $affixesToDelete = [
            'Devils Arrow',
            'Sinister Dance',
            'Eye For Gold',
            'Weapons Glory',
            'Armour Smiths Hopes',
            'Spell Crafters Blood',
            'Astral Ring',
            'Desert Winds',
            'Enchantress Luck',
            'Pact For Accuracy',
            'Dancing Moon Light',
            'Treasures Dreams',
            'Smithies Prayer',
            'Fires Of Armour',
            'Dream Of Magic',
            'Ring Smiths Fate',
            'The Earths Winds',
            'Enchantress Spell',
            'Deaths Laugh',
            'Fleeting Hopes',
            'Eye For Treasure',
            'Holy Weapons',
            'Demonic Armour',
            'Dragons Tongue',
            'Celestial Rings',
            'Astral Relics',
            'Demonic Enchantments',
            'Serial Killer Aim',
            'Run Faster',
            'Treasures Curse',
            'Weapons Rune',
            'Armour Smiths Curse',
            'Angelic Spell Crafting',
            'Curse of the Chains',
            'Embers and Ashes',
            'Enchanted Ice',
            'Rangers Luck',
            "Rumor's Movement",
            'Smell of Gold',
            'Godly Weapon Crafting',
            'Angelic Armour Smithing',
            'Divine Magic Crafting',
            'Devilish Ring Crafting',
            'Enchanted Labyrinth',
            'Dark Pact',
            'Deaths Accuracy',
            'Dancers Moves',
            'Thieves Eye',
            'Weapon Crafter Spell',
            'Blacksmiths Heart',
            'Spell Weavers Thoughts',
            'Ring Makers Inspiration',
            'Artifact Hunter',
        ];

        foreach ($affixesToDelete as $affixToDelete) {
            $affix = ItemAffix::where('name', $affixesToDelete)->first();

            if (!is_null($affix)) {
                $itemAffixService->deleteAffix($affix);
            }
        }

        $items = Item::whereNotNull('item_suffix_id')->get();

        foreach ($items as $item) {
            $this->deleteItem($item);
        }

        $items = Item::whereNotNull('item_prefix_id')->get();

        foreach ($items as $item) {
            $this->deleteItem($item);
        }
    }

    protected function deleteItem(Item $item) {
        $slots = InventorySlot::where('item_id', $item->id)->get();
        $name  = $item->affix_name;

        if ($slots->isEmpty()) {
            $item->delete();

            return;
        }

        foreach($slots as $slot) {
            $character = $slot->inventory->character;

            $slot->delete();

            $gold = SellItemCalculator::fetchTotalSalePrice($item);

            $character->gold += $gold;
            $character->save();

            $character = $character->refresh();

            event(new ServerMessageEvent($character->user, 'deleted_item', $name));
            event(new UpdateTopBarEvent($character));
        }

        $item->delete();
    }
}
