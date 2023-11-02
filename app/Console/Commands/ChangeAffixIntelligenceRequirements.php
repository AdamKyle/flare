<?php

namespace App\Console\Commands;

use App\Flare\ExponentialCurve\Curve\ExponentialAttributeCurve;
use App\Flare\Models\ItemAffix;
use App\Flare\Values\ItemAffixType;
use Illuminate\Console\Command;

class ChangeAffixIntelligenceRequirements extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'change:affix-intelligence-requirements';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Change The requirement of the affixes for their intelligence';

    /**
     * Execute the console command.
     */
    public function handle(ExponentialAttributeCurve $exponentialAttributeCurve) {

        foreach (ItemAffixType::$values as $type) {

            if (!isset(ItemAffixType::$dropDownValues[$type])) {
                continue;
            }

            $itemAffixesCount = ItemAffix::where('affix_type', $type)->count();

            if ($itemAffixesCount > 0) {
                $curve = $exponentialAttributeCurve->setMin(10)
                    ->setMax(250000)
                    ->setIncrease(1000)
                    ->setRange($itemAffixesCount, true);

                $data = $curve->generateValues($itemAffixesCount, true);

                foreach (ItemAffix::where('affix_type', $type)->get() as $index => $itemAffix) {
                    $itemAffix->update([
                        'int_required' => $data[$index],
                    ]);
                }

                $this->line('Updated type: ' . ItemAffixType::$dropDownValues[$type]);
            }
        }
    }
}
