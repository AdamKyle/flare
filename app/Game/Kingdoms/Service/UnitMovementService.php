<?php

namespace App\Game\Kingdoms\Service;

use App\Game\Maps\Calculations\DistanceCalculation;

class UnitMovementService {

    private DistanceCalculation $distanceCalculation;

    public function __construct(DistanceCalculation $distanceCalculation) {
        $this->distanceCalculation = $distanceCalculation;
    }

    public function getKingdomUnitTravelData(): array {
        return [];
    }
}
