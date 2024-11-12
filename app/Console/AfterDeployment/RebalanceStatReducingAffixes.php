<?php

namespace App\Console\AfterDeployment;

use App\Flare\ExponentialCurve\Curve\LinearAttributeCurve;
use App\Flare\Models\ItemAffix;
use App\Flare\Values\ItemAffixType;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

class RebalanceStatReducingAffixes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rebalance:stat-reducing-affixes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rebalance all affixes that reduce enemy stats';

    private array $statsToModifiy = [
        'str_reduction',
        'dur_reduction',
        'dex_reduction',
        'chr_reduction',
        'int_reduction',
        'agi_reduction',
        'focus_reduction',
    ];

    /**
     * Execute the console command.
     */
    public function handle(LinearAttributeCurve $linearAttributeCurve)
    {
        $itemAffixes = ItemAffix::where('affix_type', ItemAffixType::STAT_REDUCTION)->where('randomly_generated', false)->get();
        $count = $itemAffixes->count();

        $statCurveData = $this->generateCurveDataForAffixes($linearAttributeCurve, $count);

        $this->setStatDetailsForAffixes($itemAffixes, $statCurveData);
    }

    private function generateCurveDataForAffixes(LinearAttributeCurve $linearAttributeCurve, int $size): array
    {

        return $linearAttributeCurve->setMin(0.01)->setMax(.65)->setIncrease(0.002)->generateValues($size);
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
