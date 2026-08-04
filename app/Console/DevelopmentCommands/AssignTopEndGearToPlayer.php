<?php

namespace App\Console\DevelopmentCommands;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Models\ItemAffix;
use App\Game\Core\Items\Services\HolyItemBonusGenerator;
use App\Game\Core\Items\Values\ArmourType;
use App\Game\Core\Items\Values\HolyItemLevel;
use App\Game\Core\Items\Values\ItemSpecialtyType;
use App\Game\Core\Items\Values\ItemType;
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
     */
    public function handle(HolyItemBonusGenerator $holyItemBonusGenerator): void
    {
        $characterName = $this->argument('characterName');

        $character = Character::where('name', $characterName)->first();

        if (is_null($character)) {
            $this->error('No character found for name: '.$characterName);

            return;
        }

        $topEndGear = Item::doesntHave('appliedHolyStacks')
            ->where('item_prefix_id', null)
            ->where('item_suffix_id', null)
            ->where('specialty_type', ItemSpecialtyType::PURGATORY_CHAINS->value)
            ->get();

        if (empty($topEndGear)) {
            $this->error('There are no purgatory items.');

            return;
        }

        $prefix = ItemAffix::where('type', 'prefix')
            ->where('randomly_generated', false)
            ->where($character->damage_stat.'_mod', '>', 0)
            ->orderBy('skill_level_required', 'desc')
            ->first();

        $suffix = ItemAffix::where('type', 'suffix')
            ->where('randomly_generated', false)
            ->where($character->damage_stat.'_mod', '>', 0)
            ->orderBy('skill_level_required', 'desc')
            ->first();

        $bar = $this->output->createProgressBar(count($topEndGear));

        foreach ($topEndGear as $topEndItem) {
            if (
                $topEndItem->type === ArmourType::SHIELD->value ||
                $topEndItem->type === ItemType::WEAPON->value ||
                $topEndItem->type === ItemType::SPELL_DAMAGE->value ||
                $topEndItem->type === ItemType::SPELL_HEALING->value ||
                $topEndItem->type === ItemType::RING->value
            ) {

                for ($i = 1; $i <= 2; $i++) {
                    $character->inventory->slots()->create([
                        'inventory_id' => $character->inventory->id,
                        'item_id' => $this->modifyItem($topEndItem, $prefix, $suffix)->id,
                    ]);

                    $character = $character->refresh();
                }

                $bar->advance();

                continue;
            }

            $character->inventory->slots()->create([
                'inventory_id' => $character->inventory->id,
                'item_id' => $this->modifyItem($topEndItem, $prefix, $suffix)->id,
            ]);

            $character = $character->refresh();

            $bar->advance();
        }

        $bar->finish();
    }

    protected function modifyItem(Item $item, ItemAffix $prefix, ItemAffix $suffix): Item
    {
        $newItem = $item->duplicate();

        $newItem->update([
            'market_sellable' => true,
            'holy_level' => 20,
            'item_suffix_id' => $suffix->id,
            'item_prefix_id' => $prefix->id,
        ]);

        $newItem = $newItem->refresh();

        return $this->applyHolyOilsToItem($newItem);
    }

    protected function applyHolyOilsToItem(Item $item): Item
    {
        $topEndOil = Item::where('type', 'alchemy')->where('name', 'like', '%Oil%')->orderBy('id', 'desc')->first();

        for ($i = 1; $i <= 20; $i++) {
            $holyItemLevel = HolyItemLevel::from($topEndOil->holy_level);

            $item->appliedHolyStacks()->create([
                'item_id' => $item->id,
                'devouring_darkness_bonus' => $holyItemBonusGenerator->getRandomDevoidanceIncrease($holyItemLevel),
                'stat_increase_bonus' => $holyItemBonusGenerator->getRandomStatIncrease($holyItemLevel) / 100,
            ]);

            $item = $item->refresh();
        }

        return $item->refresh();
    }
}
