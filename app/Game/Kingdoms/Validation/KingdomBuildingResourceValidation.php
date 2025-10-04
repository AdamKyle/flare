<?php

namespace App\Game\Kingdoms\Validation;

use App\Flare\Models\Kingdom;
use App\Flare\Models\KingdomBuilding;
use App\Game\Kingdoms\Service\KingdomBuildingService;

class KingdomBuildingResourceValidation
{
    public function __construct(private KingdomBuildingService $kingdomBuildingService) {}

    public function getCostsForBuilding(KingdomBuilding $building): array
    {
        return $this->kingdomBuildingService->getBuildingCosts($building);
    }

    public function isMissingResources(KingdomBuilding $building): bool
    {
        $costs = $this->kingdomBuildingService->getBuildingCosts($building);
        $kingdom = $building->kingdom;

        foreach ($costs as $resource => $cost) {
            if ($kingdom->{'current_'.$resource} < $cost) {
                return true;
            }
        }

        return false;
    }

    public function getMissingCosts(Kingdom $kingdom, array $costs): array
    {
        $missingCosts = [];

        foreach ($costs as $resource => $cost) {
            $amountMissing = $cost - $kingdom->{'current_'.$resource};

            if ($amountMissing > 0) {
                $missingCosts[$resource] = $amountMissing;
            }
        }

        return $missingCosts;
    }
}
