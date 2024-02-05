<?php

namespace App\Game\Kingdoms\Service;

use App\Flare\Models\BuildingExpansionQueue;
use App\Flare\Models\BuildingInQueue;
use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
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
        $this->updateKingdom = $updateKingdom;
    }

    public function fetchExpansionDetails(KingdomBuilding $building): array {
        $buildingExpansion = $building->buildingExpansion;

        if (is_null($buildingExpansion)) {
            return $this->successResult([
                'expansion_details' =>[
                    'resource_costs' => ResourceBuildingExpansionBaseValue::resourceCostsForExpansion(),
                    'gold_bars_cost' => ResourceBuildingExpansionBaseValue::BASE_GOLD_BARS_REQUIRED,
                    'minutes_until_next_expansion' => ResourceBuildingExpansionBaseValue::BASE_MINUTES_REQUIRED,
                    'expansion_count' => 0,
                    'expansions_left' => ResourceBuildingExpansionBaseValue::MAX_EXPANSIONS,
                    'resource_increases' => ResourceBuildingExpansionBaseValue::BASE_RESOURCE_GAIN,
                ],
                'time_left' => $this->getTimeLeft($building),
            ]);
        }

        return $this->successResult([
            'expansion_details' => $buildingExpansion,
            'time_left' => $this->getTimeLeft($building),
        ]);

    }

    protected function getTimeLeft(KingdomBuilding $building): int {
        $queue = BuildingExpansionQueue::where('building_id', $building->id)->first();
        $timeLeft = 0;

        if (!is_null($queue)) {
            $end = $queue->completed_at;
            $current = now();

            $timeLeft = $current->diffInSeconds($end, false);
        }

        return $timeLeft;
    }

    public function startExpansion(KingdomBuilding $building): array {

        $buildingExpansion = $building->buildingExpansion;

        $expansionInQueueForBuilding = BuildingExpansionQueue::where('building_id', $building->id)->first();

        $kingdomGoldBars = $building->kingdom->gold_bars;

        if (!is_null($expansionInQueueForBuilding)) {
            return $this->errorResult('You already have an expansion in progress for this building.');
        }

        if (!is_null($buildingExpansion)) {

            if ($buildingExpansion->expansions_left <= 0) {
                return $this->errorResult('You cannot expand this building any further.');
            }

            if (!$this->canAffordResourceCost($building) || $kingdomGoldBars < $buildingExpansion->gold_bars_cost) {
                return $this->errorResult('You annot afford to expand this building.');
            }

            $this->subtractCostFromKingdom($buildingExpansion);

            $timeNeeded = now()->addMinutes($buildingExpansion->minutes_until_next_expansion);

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

        if (!$this->canAffordResourceCost($building) ) {
            return $this->errorResult('You cannot afford to expand this building.');
        }

         if ($kingdomGoldBars < ResourceBuildingExpansionBaseValue::BASE_GOLD_BARS_REQUIRED) {
             return $this->errorResult('You cannot afford to expand this building.');
         }

        $this->subtractBaseCostFromKingdom($building->kingdom);

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
        $resourceCosts = ResourceBuildingExpansionBaseValue::resourceCostsForExpansion();

        if (!is_null($buildingExpansion)) {
            $resourceCosts = $buildingExpansion->resource_costs;
        }

        $kingdom = $building->kingdom;

        $costs = collect($resourceCosts)->filter(function ($key, $value) use ($kingdom) {
            return $kingdom->{'current_' . $value} >= $key;
        });

        return $costs->isNotEmpty() && $costs->count() === count($resourceCosts);
    }

    protected function subtractCostFromKingdom(KingdomBuilding $building): void {

        $buildingExpansion = $building->buildingExpansion;
        $kingdom = $building->kingdom;

        $result = collect($buildingExpansion)->map(function ($key, $value) use ($kingdom) {
            $remainingValue = $kingdom->{'current_' . $value} - $key;
            return ['current_' . $value => $remainingValue] > 0 ? $remainingValue : 0;
        })->collapse()->all();

        $kingdom->update($result);

        $this->updateKingdom->updateKingdom($kingdom->refresh());
    }

    protected function subtractBaseCostFromKingdom(Kingdom $kingdom): void {
        $result = collect(ResourceBuildingExpansionBaseValue::resourceCostsForExpansion())->map(function ($key, $value) use ($kingdom) {
            $remainingValue = $kingdom->{'current_' . $value} - $key;
            return ['current_' . $value => $remainingValue];
        })->collapse()->all();

        $kingdom->update($result);

        $this->updateKingdom->updateKingdom($kingdom->refresh());
    }
}
