<?php

namespace App\Game\Gambler\Handlers;

use App\Game\Core\Chance\RandomNumberGenerator;
use App\Game\Gambler\Values\CurrencyValue;

class SpinHandler
{
    public function __construct(private readonly RandomNumberGenerator $randomNumberGenerator) {}

    public function roll(): array
    {

        $rollOne = $this->randomNumberGenerator->numberBetween(0, count(CurrencyValue::getIcons()) - 1);
        $rollTwo = $this->randomNumberGenerator->numberBetween(0, count(CurrencyValue::getIcons()) - 1);
        $rollThree = $this->randomNumberGenerator->numberBetween(0, count(CurrencyValue::getIcons()) - 1);

        $rolls = [$rollOne, $rollTwo, $rollThree];

        $difference = array_diff_assoc($rolls, array_unique($rolls));
        $difference = array_values($difference);

        return [
            'difference' => $difference,
            'rolls' => $rolls,
        ];
    }

    public function processRoll(array $rollInfo): array
    {
        $difference = $rollInfo['difference'];
        $rolls = $rollInfo['rolls'];

        $matching = null;
        $matchingAmount = 0;

        if (count($difference) === 1) {
            $index = $difference[0];
            $itemRolled = CurrencyValue::getIcons()[$index]['type'];

            if (in_array($itemRolled, CurrencyValue::getValues())) {
                $matchingAmount = 2;
                $matching = $itemRolled;
            }
        }

        if (count($difference) === 2) {
            $index = $difference[0];
            $itemRolled = CurrencyValue::getIcons()[$index]['type'];

            if (in_array($itemRolled, CurrencyValue::getValues())) {
                $matchingAmount = 3;
                $matching = $itemRolled;
            }
        }

        return [
            'roll' => $rolls,
            'matching' => $matching,
            'matchingAmount' => $matchingAmount,
        ];
    }
}
