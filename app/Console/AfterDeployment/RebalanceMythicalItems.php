<?php

namespace App\Console\AfterDeployment;

use App\Flare\Models\Item;
use App\Flare\Models\ItemAffix;
use Illuminate\Console\Command;

class RebalanceMythicalItems extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rebalance:mythical-items';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rebalance Mythical Items';

    private array $affixStats = [
        'base_damage_mod',
        'base_ac_mod',
        'base_healing_mod',
        'str_mod',
        'dur_mod',
        'dex_mod',
        'chr_mod',
        'int_mod',
        'agi_mod',
        'focus_mod',
        'str_reduction',
        'dur_reduction',
        'dex_reduction',
        'chr_reduction',
        'int_reduction',
        'agi_reduction',
        'focus_reduction',
        'reduces_enemy_stats',
        'steal_life_amount',
        'entranced_chance',
        'damage_amount',
        'skill_training_bonus',
        'skill_bonus',
        'skill_reduction',
        'resistance_reduction',
        'devouring_light',
    ];

    const ORIGINAL_MYTHIC_PRICE = 500_000_000_000;

    const NEW_MYTHICAL_PRICE = 10_000_000_000;

    const MAX_PERCENTAGE = .80;

    const MAX_DAMAGE = .60;

    /**
     * Execute the console command.
     */
    public function handle()
    {

        Item::where('is_mythic', true)->chunkById(100, function ($items) {
            foreach ($items as $item) {
                $this->manageItemAffixes($item);
            }
        });
    }

    private function manageItemAffixes(Item $item): void
    {
        if (!is_null($item->item_suffix_id)) {
            $affix = $item->itemSuffix;

            $itemAffix = $this->changeStatsOnAffix($affix);

            if ($itemAffix->cost > self::ORIGINAL_MYTHIC_PRICE) {
                $itemAffix->cost = self::NEW_MYTHICAL_PRICE;
            }

            $itemAffix->save();
        }

        if (!is_null($item->item_prefix_id)) {
            $affix = $item->itemPrefix;

            $itemAffix = $this->changeStatsOnAffix($affix);

            if ($itemAffix->cost > self::ORIGINAL_MYTHIC_PRICE) {
                $itemAffix->cost = self::NEW_MYTHICAL_PRICE;
            }

            $itemAffix->save();
        }
    }

    private function changeStatsOnAffix(ItemAffix $affix): ItemAffix
    {
        foreach ($this->affixStats as $stat) {

            if ($stat === 'damage_amount' && $affix->{$stat} > self::MAX_DAMAGE) {
                $affix->{$stat} = self::MAX_DAMAGE;

                continue;
            }

            if ($affix->{$stat} > self::MAX_PERCENTAGE) {
                $affix->{$stat} = self::MAX_PERCENTAGE;
            }
        }

        return $affix;
    }
}
