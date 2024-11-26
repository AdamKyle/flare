<?php

namespace App\Console\AfterDeployment;

use App\Flare\ExponentialCurve\Curve\LinearAttributeCurve;
use App\Flare\Models\Item;
use Illuminate\Console\Command;

class RebalanceTrinkets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rebalance:trinkets';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rebalance Trinkets';

    /**
     * Execute the console command.
     */
    public function handle(LinearAttributeCurve $linearAttributeCurve)
    {
        $trinkets = Item::where('type', 'trinket')->orderBy('skill_level_required')->get();

        $trinketCount = $trinkets->count();

        $curveDataForTrinkets = $this->generateCurveDataForTrinkets($linearAttributeCurve, $trinketCount);

        foreach ($trinkets as $index => $trinket) {
            $trinket->update([
                'ambush_chance' => $curveDataForTrinkets[$index],
                'ambush_resistance' => $curveDataForTrinkets[$index],
                'counter_chance' => $curveDataForTrinkets[$index],
                'counter_resistance' => $curveDataForTrinkets[$index],
            ]);
        }
    }

    private function generateCurveDataForTrinkets(LinearAttributeCurve $linearAttributeCurve, int $size): array
    {

        return $linearAttributeCurve->setMin(0.05)->setMax(.95)->setIncrease(0.02)->generateValues($size);
    }
}
