<?php

namespace App\Console\DevelopmentCommands;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Models\ItemAffix;
use App\Flare\Values\ArmourTypes;
use App\Flare\Values\ItemHolyValue;
use App\Flare\Values\ItemSpecialtyType;
use App\Flare\Values\SpellTypes;
use App\Flare\Values\WeaponTypes;
use Illuminate\Console\Command;

class AssignTopEndGearToPlayer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'assign:top-end-gear {characterName}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign Fully Enchanted Purgatory gear to player with full holy stacks';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void {
        $characterName = $this->argument('characterName');

        $character = Character::where('name', $characterName)->first();

        if (is_null($character)) {
            $this->error('No character found for name: ' . $characterName);

            return;
        }

        $purgatoryGear = Item::doesntHave('appliedHolyStacks')
                             ->where('item_prefix_id', null)
                             ->where('item_suffix_id', null)
                             ->where('specialty_type', ItemSpecialtyType::PURGATORY_CHAINS)
                             ->get();

        if (empty($purgatoryGear)) {
            $this->error('There are no purgatory items.');

            return;
        }

        $prefix = ItemAffix::where('type', 'prefix')
                           ->where('randomly_generated', false)
                           ->where($character->damage_stat . '_mod', '>', 0)
                           ->orderBy('skill_level_required', 'desc')
                           ->first();

        $suffix = ItemAffix::where('type', 'suffix')
                           ->where('randomly_generated', false)
                           ->where($character->damage_stat . '_mod', '>', 0)
                           ->orderBy('skill_level_required', 'desc')
                           ->first();


        $bar = $this->output->createProgressBar(count($purgatoryGear));

        foreach ($purgatoryGear as $purgItem) {
            if ($purgItem->type === ArmourTypes::SHIELD ||
                $purgItem->type === SpellTypes::DAMAGE ||
                $purgItem->type === SpellTypes::HEALING ||
                $purgItem->type === WeaponTypes::RING)  {

                for ($i = 1; $i <= 2; $i++) {
                    $character->inventory->slots()->create([
                        'inventory_id' => $character->inventory->id,
                        'item_id'      => $this->modifyItem($purgItem, $prefix, $suffix)->id,
                    ]);

                    $character = $character->refresh();
                }

                $bar->advance();

                continue;
            }

            $character->inventory->slots()->create([
                'inventory_id' => $character->inventory->id,
                'item_id'      => $this->modifyItem($purgItem, $prefix, $suffix)->id,
            ]);

            $character = $character->refresh();

            $bar->advance();
        }

        $bar->finish();
    }

    protected function modifyItem(Item $item, ItemAffix $prefix, ItemAffix $suffix): Item {
        $newItem = $item->duplicate();

        $newItem->update([
            'market_sellable' => true,
            'holy_level'      => 20,
            'item_suffix_id'  => $suffix->id,
            'item_prefix_id'  => $prefix->id,
        ]);

        $newItem = $newItem->refresh();

       return $this->applyHolyOilsToItem($newItem);
    }

    protected function applyHolyOilsToItem(Item $item): Item {
        $topEndOil = Item::where('type', 'alchemy')->where('name', 'like', '%Oil%')->orderBy('id', 'desc')->first();

        for ($i = 1; $i <= 20; $i++) {
            $holyItemEffect = new ItemHolyValue($topEndOil->holy_level);

            $item->appliedHolyStacks()->create([
                'item_id'                  => $item->id,
                'devouring_darkness_bonus' => $holyItemEffect->getRandomDevoidanceIncrease(),
                'stat_increase_bonus'      => $holyItemEffect->getRandomStatIncrease() / 100,
            ]);

            $item = $item->refresh();
        }

        return $item->refresh();
    }
}
