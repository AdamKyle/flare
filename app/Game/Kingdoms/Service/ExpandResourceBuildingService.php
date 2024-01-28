<?php

namespace App\Game\Kingdoms\Service;

use App\Flare\Models\Character;
use App\Flare\Models\KingdomBuilding;
use App\Game\Core\Traits\ResponseBuilder;

class ExpandResourceBuildingService {

    use ResponseBuilder;

    private UpdateKingdom $updateKingdom;

    const HOURS_TO_SECONDS = 3600;

    const MINUTES_TO_SECONDS = 60;

    public function __construct(UpdateKingdom $updateKingdom) {
        $this->updateKIngdom = $updateKingdom;
    }

    public function startExpansion(Character $character, KingdomBuilding $building) {

        $buildingExpansion = $building->buildingExpansion;

        if (!is_null($buildingExpansion)) {

            if ($buildingExpansion->expansions_left <= 0) {
                return $this->errorResult('You cannot expand this building any further.');
            }

            $kingdomGoldBars = $building->kingdom->gold_bars;

            if (!$this->canAffordResourceCost($building) || $kingdomGoldBars < $buildingExpansion->gold_bars_cost) {
                return $this->errorResult('You annot afford to expand this building.');
            }

            $this->subtractCostFromKingdom($buildingExpansion);

            // Create Expansion Record

            // Start Job

            return $this->successResult([
                'time_left' => $buildingExpansion->hour_for_next_expansion * self::HOURS_TO_SECONDS,
                'message' => 'Currently expanding your building!'
            ]);
        }

        // Create Expansion Record

        // Start Job

        return $this->successResult([
            'time_left' => 15 * self::MINUTES_TO_SECONDS,
            'message' => 'Currently expanding your building!'
        ]);
    }

    protected function canAffordResourceCost(KingdomBuilding $building): bool {
        $buildingExpansion = $building->buildingExpansion;
        $kingdom = $building->kingdom;

        return collect($buildingExpansion->resource_costs)->filter(function ($key, $value) use ($kingdom) {
            return $value >= $kingdom->$key;
        })->isEmpty();
    }

    protected function subtractCostFromKingdom(KingdomBuilding $building): void {

        $buildingExpansion = $building->buildingExpansion;
        $kingdom = $building->kingdom;

        $result = collect($buildingExpansion)->map(function ($key, $value) use ($kingdom) {
            $remainingValue = min(0,  $kingdom->$key - $value);
            return [$key => $remainingValue];
        })->collapse()->all();

        $kingdom->update($result);

        $this->updateKingdom->updateKingdom($kingdom->refresh());
    }
}
