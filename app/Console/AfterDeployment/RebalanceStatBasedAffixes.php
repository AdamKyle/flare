<?php

namespace App\Console\AfterDeployment;

use App\Flare\ExponentialCurve\Curve\ExponentialAttributeCurve;
use App\Flare\ExponentialCurve\Curve\LinearAttributeCurve;
use App\Flare\Models\ItemAffix;
use App\Flare\Values\ItemAffixType;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

class RebalanceStatBasedAffixes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rebalance:stat-based-affixes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rebalance all affixes that affect stats';

    private array $affixTypesToRebalance = [
        ItemAffixType::STAT_MODIFIERS,
        ItemAffixType::WEAPON_CRAFTING,
        ItemAffixType::ARMOUR_CRAFTING,
        ItemAffixType::SPELL_CRAFTING,
        ItemAffixType::RING_CRAFTING,
        ItemAffixType::ENCHANTMENT_CRAFTING,
        ItemAffixType::DODGE,
        ItemAffixType::LOOTING,
        ItemAffixType::CASTING_ACCURACY,
        ItemAffixType::CRITICALITY
    ];

    private array $statsToModifiy = [
        'str_mod',
        'dex_mod',
        'int_mod',
        'dur_mod',
        'chr_mod',
        'agi_mod',
        'focus_mod',
    ];

    /**
     * Execute the console command.
     */
    public function handle(LinearAttributeCurve $linearAttributeCurve)
    {
        foreach ($this->affixTypesToRebalance as $typeToRebalance) {
            $itemAffixes = ItemAffix::where('affix_type', $typeToRebalance)->where('randomly_generated', false)->get();
            $count = $itemAffixes->count();

            $statCurveData = $this->generateCurveDataForAffixes($linearAttributeCurve, $count);

            $this->setStatDetailsForAffixes($itemAffixes, $statCurveData);
        }
    }

    private function generateCurveDataForAffixes(LinearAttributeCurve $linearAttributeCurve, int $size): array
    {

        return $linearAttributeCurve->setMin(0.01)->setMax(1.0)->setIncrease(0.002)->generateValues($size);
    }

    private function setStatDetailsForAffixes(Collection $affixes, array $statCurve): void
    {
        foreach ($affixes as $index => $affix) {
            $itemAffix = $this->modifyStatsOfAffix($affix, $statCurve[$index]);

            $itemAffix->save();
        }
    }

    private function modifyStatsOfAffix(ItemAffix $itemAffix, float $newValue): ItemAffix
    {
        foreach ($this->statsToModifiy as $stat) {
            if (($itemAffix->{$stat} > 0)) {
                $itemAffix->{$stat} = $newValue;
            }
        }

        return $itemAffix;
    }
}
