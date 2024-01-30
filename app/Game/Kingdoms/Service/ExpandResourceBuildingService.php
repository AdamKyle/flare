<?php

namespace App\Game\Kingdoms\Service;

use App\Flare\Models\BuildingExpansionQueue;
use App\Flare\Models\BuildingInQueue;
use App\Flare\Models\Character;
use App\Flare\Models\KingdomBuilding;
use App\Flare\Models\KingdomBuildingExpansion;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Kingdoms\Jobs\ExpandResourceBuilding;
use App\Game\Kingdoms\Values\KingdomMaxValue;
use App\Game\Kingdoms\Values\ResourceBuildingExpansionBaseValue;
use Carbon\Carbon;

class ExpandResourceBuildingService {

    use ResponseBuilder;

    private UpdateKingdom $updateKingdom;

    public function __construct(UpdateKingdom $updateKingdom) {
        $this->updateKIngdom = $updateKingdom;
    }

    public function fetchExpansionDetails(KingdomBuilding $building): array {
        $buildingExpansion = $building->buildingExpansion;

        if (!is_null($buildingExpansion)) {
            return $this->successResult([
                'resource_costs' => ResourceBuildingExpansionBaseValue::resourceCostsForExpansion(),
                'gold_bars_cost' => ResourceBuildingExpansionBaseValue::BASE_GOLD_BARS_REQUIRED,
                'minutes_until_next_expansion' => ResourceBuildingExpansionBaseValue::BASE_MINUTES_REQUIRED,
                'expansion_count' => 0,
                'expansions_left' => ResourceBuildingExpansionBaseValue::MAX_EXPANSIONS,
            ]);
        }

        return $this->successResult($buildingExpansion);

    }

    public function startExpansion(KingdomBuilding $building): array {

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

        $timeNeeded = now()->addMinutes($buildingExpansion->minutes_until_next_expansion);

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

    public function cancelExpansion(KingdomBuilding $building): array {
        $buildingExpansion = $building->buildingExpansion;
        $queue = BuildingExpansionQueue::where('building');

        if (is_null($buildingExpansion)) {
            return $this->errorResult('This building has never been expanded.');
        }

        if (is_null($queue)) {
            return $this->errorResult('There is no expansion in progress to cancel.');
        }

        $resourcesToGainBack = $this->resourceCancellationCalculation($queue);

        if ($resourcesToGainBack <= 0) {
            return $this->errorResult('Expansion is too close to finishing to cancel.');
        }

        $kingdom = $building->kingdom;

        foreach ($buildingExpansion->resource_costs as $key => $value) {

            $newValue = $kingdom->{'current_' . $key} + ($kingdom->{'current_' . $key}  * $resourcesToGainBack);

            if ($newValue > $kingdom->{'max_' . $key}) {
                $newValue = $kingdom->{'max_' . $key};
            }

            $kingdom->{'current_' . $key} = $newValue;
        }

        $kingdom->save();

        $this->updateKingdom->updateKingdom($kingdom->refresh());

        $queue->delete();

        return $this->successResult([
            'message' => 'Expansion has been canceled. You got back: ' . number_format($resourcesToGainBack * 100) . '% of your resources spent - with the exception of Gold Bars.',
            'expansion_details' => $buildingExpansion->refresh()
        ]);
    }

    protected function resourceCancellationCalculation(BuildingExpansionQueue $queue): float {
        $start   = Carbon::parse($queue->started_at)->timestamp;
        $end     = Carbon::parse($queue->completed_at)->timestamp;
        $current = Carbon::parse(now())->timestamp;

        $completed = (($current - $start) / ($end - $start));

        if ($completed === 0) {
            return 0;
        } else {
            return 1 - $completed;
        }
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
