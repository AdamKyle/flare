<?php

namespace App\Console\AfterDeployment;

use App\Flare\ExponentialCurve\Curve\LinearAttributeCurve;
use App\Flare\Models\ItemAffix;
use App\Flare\Values\ItemAffixType;
use Illuminate\Console\Command;

class RebalanceBaseModifierAffixes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rebalance:base-modifier-affixes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rebalances all the base modifier affixes';

    /**
     * Execute the console command.
     */
    public function handle(LinearAttributeCurve $linearAttributeCurve)
    {
        $this->rebalanceAttackBasedModifiers($linearAttributeCurve);
        $this->rebalanceArmourBaseModifiers($linearAttributeCurve);
        $this->rebalanceHealingBaseModifiers($linearAttributeCurve);
    }

    private function rebalanceAttackBasedModifiers(LinearAttributeCurve $linearAttributeCurve)
    {
        $itemAffixes = ItemAffix::where('affix_type', ItemAffixType::BASE_MODIFIERS)->where('randomly_generated', false)->orderBy('skill_level_required')->get();
        $itemAffixSize = $itemAffixes->count();

        $statCurve = $this->generateCurveDataForAffixes($linearAttributeCurve, $itemAffixSize, 0.55);

        foreach ($itemAffixes as $index => $affix) {
            $affix->base_damage_mod = $statCurve[$index];
            $affix->save();
        }
    }

    private function rebalanceArmourBaseModifiers(LinearAttributeCurve $linearAttributeCurve)
    {
        $itemAffixes = ItemAffix::where('affix_type', ItemAffixType::BASE_MODIFIERS)->where('randomly_generated', false)->orderBy('skill_level_required')->get();
        $itemAffixSize = $itemAffixes->count();

        $statCurve = $this->generateCurveDataForAffixes($linearAttributeCurve, $itemAffixSize, 1.0);

        foreach ($itemAffixes as $index => $affix) {
            $affix->base_ac_mod = $statCurve[$index];
            $affix->save();
        }
    }

    private function rebalanceHealingBaseModifiers(LinearAttributeCurve $linearAttributeCurve)
    {
        $itemAffixes = ItemAffix::where('affix_type', ItemAffixType::BASE_MODIFIERS)->where('randomly_generated', false)->orderBy('skill_level_required')->get();
        $itemAffixSize = $itemAffixes->count();

        $statCurve = $this->generateCurveDataForAffixes($linearAttributeCurve, $itemAffixSize, 0.65);

        foreach ($itemAffixes as $index => $affix) {
            $affix->base_healing_mod = $statCurve[$index];
            $affix->save();
        }
    }

    private function generateCurveDataForAffixes(LinearAttributeCurve $linearAttributeCurve, int $size, float $max): array
    {

        return $linearAttributeCurve->setMin(0.012)->setMax($max)->setIncrease(0.01)->generateValues($size);
    }
}
