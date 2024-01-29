<?php

namespace App\Game\Kingdoms\Service;

use App\Flare\Models\BuildingExpansionQueue;
use App\Flare\Models\Character;
use App\Flare\Models\KingdomBuilding;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Kingdoms\Jobs\ExpandResourceBuilding;
use App\Game\Kingdoms\Values\ResourceBuildingExpansionBaseValue;

class ExpandResourceBuildingService {

    use ResponseBuilder;

    private UpdateKingdom $updateKingdom;

    public function __construct(UpdateKingdom $updateKingdom) {
        $this->updateKIngdom = $updateKingdom;
    }

    public function startExpansion(Character $character, KingdomBuilding $building) {

        $buildingExpansion = $building->buildingExpansion;

        $expansionInQueueForBuilding = BuildingExpansionQueue::where('building_id', $building->id)->first();

        if (!is_null($expansionInQueueForBuilding)) {
            return $this->errorResult('You already have an expansion in progress for this building.');
        }

        if (!is_null($buildingExpansion)) {

            if ($buildingExpansion->expansions_left <= 0) {
                return $this->errorResult('You cannot expand this building any further.');
            }

            $kingdomGoldBars = $building->kingdom->gold_bars;

            if (!$this->canAffordResourceCost($building) || $kingdomGoldBars < $buildingExpansion->gold_bars_cost) {
                return $this->errorResult('You annot afford to expand this building.');
            }

            $this->subtractCostFromKingdom($buildingExpansion);

            $timeNeeded = now()->addMinutes(ResourceBuildingExpansionBaseValue::BASE_MINUTES_REQUIRED);

            $expansionInQueueForBuilding = BuildingExpansionQueue::create([
                'character_id' => $building->kingdom->character_id,
                'kingdom_id'   => $building->kingdom_id,
                'building_id'  => $building->id,
                'completed_at' => $timeNeeded,
                'started_at'   => now(),
            ]);

            ExpandResourceBuilding::dispatch($building, $building->kingdom->character->user, $expansionInQueueForBuilding->id)->delay($timeNeeded);

            return $this->successResult([
                'time_left' => $timeNeeded->diffInSeconds(),
                'message' => 'Currently expanding your building!'
            ]);
        }

        $timeNeeded = now()->addMinutes(ResourceBuildingExpansionBaseValue::BASE_MINUTES_REQUIRED * $buildingExpansion->expansion_count);

        BuildingExpansionQueue::create([
            'character_id' => $building->kingdom->character_id,
            'kingdom_id'   => $building->kingdom_id,
            'building_id'  => $building->id,
            'completed_at' => $timeNeeded,
            'started_at'   => now(),
        ]);

        ExpandResourceBuilding::dispatch($building, $building->kingdom->character->user, $expansionInQueueForBuilding->id)->delay($timeNeeded);

        return $this->successResult([
            'time_left' => $timeNeeded->diffInSeconds(),
            'message' => 'Currently expanding your building!'
        ]);
    }

    protected function canAffordResourceCost(KingdomBuilding $building): bool {
        $buildingExpansion = $building->buildingExpansion;
        $kingdom = $building->kingdom;

        return collect($buildingExpansion->resource_costs)->filter(function ($key, $value) use ($kingdom) {
            return $value >= $kingdom->{'current_' . $key};
        })->isEmpty();
    }

    protected function subtractCostFromKingdom(KingdomBuilding $building): void {

        $buildingExpansion = $building->buildingExpansion;
        $kingdom = $building->kingdom;

        $result = collect($buildingExpansion)->map(function ($key, $value) use ($kingdom) {
            $remainingValue = min(0,  $kingdom->{'current_' . $key} - $value);
            return ['current_' . $key => $remainingValue];
        })->collapse()->all();

        $kingdom->update($result);

        $this->updateKingdom->updateKingdom($kingdom->refresh());
    }
}
