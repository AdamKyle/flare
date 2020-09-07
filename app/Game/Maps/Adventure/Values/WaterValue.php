<?php

namespace App\Game\Maps\Adventure\Values;

class WaterValue {

    public function isWaterTile(int $color): bool {
        // These repersent water:
        $invalidColors = [
            115217255, 114217255, 112219255, 112217247, 106222255, 117217251, 115223255
        ];

        return in_array($color, $invalidColors);
    }
}