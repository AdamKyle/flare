<?php

namespace App\Game\Maps\Calculations;

use Carbon\Carbon;

class DistanceCalculation {

    public function calculatePixel(int $gotToX, int $gotToY, int $fromX, int $fromY): int {
        $distanceX = pow(($gotToX - $fromX), 2);
        $distanceY = pow(($gotToY - $fromY), 2);

        $distance  = $distanceX + $distanceY;
        $distance  = sqrt($distance);

        return round($distance);
    }

    public function calculateMinutes(int $distance): int {
        return round($distance / 60);
    }
}