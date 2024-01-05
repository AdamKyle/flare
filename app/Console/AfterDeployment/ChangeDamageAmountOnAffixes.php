<?php

namespace App\Console\AfterDeployment;

use App\Flare\ExponentialCurve\Curve\ExponentialAttributeCurve;
use App\Flare\Models\ItemAffix;
use App\Flare\Values\ItemAffixType;
use App\Flare\Values\RandomAffixDetails;
use Illuminate\Console\Command;

class ChangeDamageAmountOnAffixes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'change:damage-amount-on-affixes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Changes the damage amount on affixes';

    /**
     * Execute the console command.
     */
    public function handle(ExponentialAttributeCurve $exponentialAttributeCurve) {
        $regularStackingAffixes = ItemAffix::whereNotNull('damage')
            ->where('randomly_generated', false)
            ->where('affix_type', ItemAffixType::DAMAGE_STACKING)
            ->orderBy('skill_level_required', 'asc')
            ->get();

        $damageAmounts = $this->generateFloats(
            $exponentialAttributeCurve,
            $regularStackingAffixes->count(),
            0.02,
            0.6,
            0.5,
            0.03,
        );

        $regularStackingAffixes->each(function($affix, $index) use($damageAmounts) {
            $affix->update([
                'damage_amount' => $damageAmounts[$index],
            ]);
        });

        $regularNonStackingAffixes = ItemAffix::whereNotNull('damage')
            ->where('randomly_generated', false)
            ->where('affix_type', ItemAffixType::DAMAGE_IRRESISTIBLE)
            ->orderBy('skill_level_required', 'asc')
            ->get();

        $damageAmounts = $this->generateFloats(
            $exponentialAttributeCurve,
            $regularNonStackingAffixes->count(),
            0.02,
            1.25,
            0.5,
            0.03,
        );

        $regularNonStackingAffixes->each(function($affix, $index) use($damageAmounts) {
            $affix->update([
                'damage_amount' => $damageAmounts[$index],
            ]);
        });

        $affixCots = [
            RandomAffixDetails::BASIC,
            RandomAffixDetails::MEDIUM,
            RandomAffixDetails::LEGENDARY,
            RandomAffixDetails::MYTHIC,
        ];

        foreach ($affixCots as $cost) {
            $stackingUniqueAffixes = ItemAffix::whereNotNull('damage')
                ->where('randomly_generated', true)
                ->where('irresistible_damage', false)
                ->where('damage_can_stack', true)
                ->where('affix_type', ItemAffixType::RANDOMLY_GENERATED)
                ->where('cost', $cost)
                ->get();

            $stackingUniqueAffixes->each(function($affix) use($cost) {
                $damageValues = (new RandomAffixDetails($cost))->getDamageRange();

                $affix->update([
                    'damage_amount' => rand($damageValues[0], $damageValues[1]) / 100,
                ]);
            });

            $nonStackingUniqueAffixes = ItemAffix::where('randomly_generated', true)
                ->where('affix_type', ItemAffixType::RANDOMLY_GENERATED)
                ->where('irresistible_damage', true)
                ->where('damage_can_stack', false)
                ->where('cost', $cost)
                ->get();

            $nonStackingUniqueAffixes->each(function($affix) use($cost) {
                $damageValues = (new RandomAffixDetails($cost))->getDamageRange();

                $affix->update([
                    'damage_amount' => rand($damageValues[0], $damageValues[1]) / 100,
                ]);
            });
        }
    }

    protected function generateFloats(ExponentialAttributeCurve $exponentialAttributeCurve, float $size, float $min, float $max, float $increase, float $range): array {
        $curve = $exponentialAttributeCurve->setMin($min)
            ->setMax($max)
            ->setIncrease($increase)
            ->setRange($range);

        return $curve->generateValues($size, false);
    }
}
