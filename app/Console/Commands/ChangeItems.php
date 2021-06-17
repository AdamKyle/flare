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

        $this->info('Cleaning Characters ...');
        $this->newLine();

        $deletingAffixes = $this->output->createProgressBar(count($affixesToDelete));

        $deletingAffixes->start();

        foreach ($affixesToDelete as $affixToDelete) {

            $affix = ItemAffix::where('name', $affixToDelete)->first();

            if (!is_null($affix)) {
                $itemAffixService->deleteAffix($affix);
            }

            $deletingAffixes->advance();
        }

        $deletingAffixes->finish();

        $this->newLine();

        $this->info('Deleting items with suffixes ...');

        $this->newLine();

        $items = Item::whereNotNull('item_suffix_id')->get();

        $suffixes = $this->output->createProgressBar($items->count());

        $suffixes->start();

        foreach ($items as $item) {
            $this->deleteItem($item);

            $suffixes->advance();
        }

        $suffixes->finish();

        $this->newLine();

        $items = Item::whereNotNull('item_prefix_id')->get();

        $this->info('Deleting items with prefixes ...');

        $this->newLine();

        $prefixes = $this->output->createProgressBar($items->count());

        $prefixes->start();

        foreach ($items as $item) {
            $this->deleteItem($item);

            $prefixes->advance();
        }

        $prefixes->finish();

        $this->newLine();

        $this->info('Resetting Skills ...');

        $this->resetSkills();

        $this->info('Finished :)');
    }

    protected function deleteItem(Item $item) {
        $slots = InventorySlot::where('item_id', $item->id)->get();
        $name  = $item->affix_name;

        if ($slots->isEmpty()) {

            $this->clearItem($item);

            return;
        }

        foreach($slots as $slot) {
            $character = $slot->inventory->character;

            $slot->delete();

            $gold = SellItemCalculator::fetchTotalSalePrice($item);

            $character->gold += ($gold - ($gold * .75));
            $character->save();

            $character = $character->refresh();

            event(new ServerMessageEvent($character->user, 'deleted_item', $name));
            event(new UpdateTopBarEvent($character));
        }

        $this->clearItem($item);
    }

    protected function resetSkills() {
        Character::chunkById(100, function($characters) {
           foreach ($characters as $character) {
               foreach ($character->skills as $skill) {
                   $skill->update([
                       'level' => 1,
                       'xp'    => 0,
                       'xp_max' => $skill->can_train ? rand(350, 700) : rand(350, 900)
                   ]);
               }
           }
        });
    }

    protected function clearItem(Item $item) {
        $itemsForSale = MarketBoard::where('item_id', $item->id)->get();

        if ($itemsForSale->isNotEmpty()) {
            foreach($itemsForSale as $forSale) {
                $forSale->delete();
            }
        }

        $itemsHistory = MarketHistory::where('item_id', $item->id)->get();

        if ($itemsHistory->isNotEmpty()) {
            foreach($itemsHistory as $history) {
                $history->delete();
            }
        }

        $item->delete();
    }
}
