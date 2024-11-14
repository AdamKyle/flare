<?php

namespace App\Console\AfterDeployment;

use App\Flare\Models\ItemAffix;
use Illuminate\Console\Command;

class ChangeLegendaryItems extends Command
{

    const BASIC_LEGENDARY_COST = 2_000_000_000;

    const MEDIUM_LEGENARY_COST = 4_000_000_000;

    const LEGENDARY_LEGENDARY_COST = 80_000_000_000;

    const MAX_PERCENTAGE = 0.50;

    const MAX_DAMAGE = 0.25;

    const MAX_LEGENDARY_AFFIX_COST = 1_000_000_000;

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

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'change:legendary-items';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rebuilds legendary items to suit the new stats';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->handleBasicLegendaryAffixes();
        $this->handleMediumLegendaryAffixes();
        $this->handleLegendaryLegendaryAffixes();
    }

    private function handleBasicLegendaryAffixes(): void
    {
        $itemAffixes = ItemAffix::where('randomly_generated', true)
            ->where('cost', self::BASIC_LEGENDARY_COST)
            ->get();

        foreach ($itemAffixes as $affix) {
            $itemAffix = $this->changeStatsOnAffix($affix);

            if ($itemAffix->cost > self::BASIC_LEGENDARY_COST) {
                $itemAffix->cost = self::MAX_LEGENDARY_AFFIX_COST;
            }

            $itemAffix->save();
        }
    }

    private function handleMediumLegendaryAffixes(): void
    {
        $itemAffixes = ItemAffix::where('randomly_generated', true)
            ->where('cost', self::MEDIUM_LEGENARY_COST)
            ->get();

        foreach ($itemAffixes as $affix) {
            $itemAffix = $this->changeStatsOnAffix($affix);

            if ($itemAffix->cost > self::MEDIUM_LEGENARY_COST) {
                $itemAffix->cost = self::MAX_LEGENDARY_AFFIX_COST;
            }

            $itemAffix->save();
        }
    }

    private function handleLegendaryLegendaryAffixes(): void
    {
        $itemAffixes = ItemAffix::where('randomly_generated', true)
            ->where('cost', self::LEGENDARY_LEGENDARY_COST)
            ->get();

        foreach ($itemAffixes as $affix) {
            $itemAffix = $this->changeStatsOnAffix($affix);

            if ($itemAffix->cost > self::LEGENDARY_LEGENDARY_COST) {
                $itemAffix->cost = self::MAX_LEGENDARY_AFFIX_COST;
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
