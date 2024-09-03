<?php

namespace App\Game\Kingdoms\Service;

use App\Flare\Models\BuildingInQueue;
use App\Flare\Models\CapitalCityBuildingQueue;
use App\Flare\Models\CapitalCityResourceRequest;
use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Flare\Models\KingdomBuilding;
use App\Flare\Models\KingdomLog;
use App\Flare\Values\KingdomLogStatusValue;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Kingdoms\Events\UpdateCapitalCityBuildingQueueTable;
use App\Game\Kingdoms\Handlers\CapitalCityBuildingManagementRequestHandler;
use App\Game\Kingdoms\Jobs\CapitalCityBuildingRequest;
use App\Game\Kingdoms\Jobs\CapitalCityResourceRequest as CapitalCityResourceRequestJob;
use App\Game\Kingdoms\Values\BuildingQueueType;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use App\Game\Kingdoms\Values\UnitCosts;
use App\Game\Maps\Calculations\DistanceCalculation;
use Facades\App\Game\Kingdoms\Validation\ResourceValidation;

class CapitalCityBuildingManagement
{
    use ResponseBuilder;

    private array $messages = [];

    public function __construct(
        private readonly CapitalCityBuildingManagementRequestHandler $capitalCityBuildingManagementRequestHandler,
        private readonly UnitMovementService $unitMovementService,
        private readonly DistanceCalculation $distanceCalculation,
        private readonly UpdateKingdom $updateKingdom) {}

    /**
     * Create the requests
     */
    public function createBuildingUpgradeRequestQueue(Character $character, Kingdom $kingdom, array $requests, string $type): array {
        return $this->capitalCityBuildingManagementRequestHandler->createRequestQueue($character, $kingdom, $requests, $type);
    }

    /**
     * Process the building request.
     *
     * - If we cannot afford the resources, then get the missing costs and send off the resource requests.
     */
    public function processBuildingRequest(CapitalCityBuildingQueue $capitalCityBuildingQueue): void
    {
        $requestData = $capitalCityBuildingQueue->building_request_data;
        $kingdom = $capitalCityBuildingQueue->kingdom;
        $character = $capitalCityBuildingQueue->character;

        foreach ($requestData as $index => $buildingUpgradeRequest) {
            $building = $kingdom->buildings()->where('id', $buildingUpgradeRequest['building_id'])->first();
            $buildingUpgradeRequest = $this->processPotentialResourceRequests($capitalCityBuildingQueue, $kingdom, $building, $character, $buildingUpgradeRequest);

            $capitalCityBuildingQueue = $capitalCityBuildingQueue->refresh();

            $requestData[$index] = $buildingUpgradeRequest;

            $capitalCityBuildingQueue->update([
                'building_request_data' => $requestData,
            ]);

            $capitalCityBuildingQueue = $capitalCityBuildingQueue->refresh();

            if ($buildingUpgradeRequest['secondary_status'] === CapitalCityQueueStatus::REQUESTING ||
                $buildingUpgradeRequest['secondary_status'] === CapitalCityQueueStatus::CANCELLED) {
                continue;
            }
        }

        $requestDataCollection = collect($requestData);

        $summedMissingCosts = $requestDataCollection->pluck('missing_costs')->map(function ($costs) {
            return collect($costs)->except('population');
        })->reduce(function ($carry, $costs) {
            return $carry->merge($costs)->map(fn ($value, $key) => ($carry->get($key, 0) + $value));
        }, collect())->toArray();


        if (!empty($summedMissingCosts)) {
            $kingdomWhoCanAfford = $this->getKingdomWhoCanAffordCosts($character, $summedMissingCosts);

            if (is_null($kingdomWhoCanAfford)) {
                $requestData = $requestData->map(function ($item) {
                    if ($item['secondary_status'] === CapitalCityQueueStatus::REQUESTING) {
                        $item['secondary_status'] = CapitalCityQueueStatus::REJECTED;
                    }

                    return $item;
                })->toArray();

                $this->messages[] = 'No kingdom could be found to request the resources for these buildings.';
            } else {

                $capitalCityBuildingQueue->update([
                    'building_request_data' => $requestData,
                    'messages' => $this->messages,
                ]);

                $capitalCityBuildingQueue = $capitalCityBuildingQueue->refresh();

                $this->createResourceRequest($capitalCityBuildingQueue, $kingdomWhoCanAfford, $kingdom, $summedMissingCosts);

                return;
            }
        }

        $hasBuildingOrRepairing = $requestDataCollection->contains(function ($item) {
            return in_array($item['secondary_status'], [CapitalCityQueueStatus::BUILDING, CapitalCityQueueStatus::REPAIRING, CapitalCityQueueStatus::REQUESTING]);
        });

        if (!$hasBuildingOrRepairing) {

            $capitalCityBuildingQueue->update([
                'building_request_data' => $requestData,
                'messages' => $this->messages,
            ]);

            $capitalCityBuildingQueue = $capitalCityBuildingQueue->refresh();

            $this->possiblyCreateLogForQueue($capitalCityBuildingQueue);

            event(new UpdateCapitalCityBuildingQueueTable($capitalCityBuildingQueue->character));

            return;
        }

        $capitalCityBuildingQueue->update([
            'building_request_data' => $requestData,
            'messages' => $this->messages,
        ]);

        $capitalCityBuildingQueue = $capitalCityBuildingQueue->refresh();

        $filteredRequestData = $requestDataCollection->filter(function ($item) {
            return in_array($item['secondary_status'], [CapitalCityQueueStatus::BUILDING, CapitalCityQueueStatus::REPAIRING]);
        })->values()->toArray();

        $this->createUpgradeRequest($character, $capitalCityBuildingQueue, $kingdom, $filteredRequestData);

        event(new UpdateCapitalCityBuildingQueueTable($capitalCityBuildingQueue->character));
    }

    /**
     * Send a log if all the buildings are done or rejected (or both)
     */
    public function possiblyCreateLogForQueue(CapitalCityBuildingQueue $capitalCityBuildingQueue): void
    {

        $requestData = $capitalCityBuildingQueue->building_request_data;
        $kingdom = $capitalCityBuildingQueue->kingdom;
        $character = $capitalCityBuildingQueue->character;

        $buildingData = [];

        foreach ($requestData as $data) {
            if ($data['secondary_status'] === CapitalCityQueueStatus::REJECTED ||
                $data['secondary_status'] === CapitalCityQueueStatus::FINISHED ||
                $data['secondary_status'] === CapitalCityQueueStatus::CANCELLED
            ) {

                $building = KingdomBuilding::where('kingdom_id', $kingdom->id)->where('id', $data['building_id'])->first();

                $buildingData[] = [
                    'building_name' => $building->name,
                    'from_level' => $data['from_level'],
                    'to_level' => $data['to_level'],
                    'type' => $data['type'],
                    'status' => $data['secondary_status'],
                ];
            }
        }

        if (count($buildingData) === count($requestData)) {
            KingdomLog::create([
                'character_id' => $character->id,
                'from_kingdom_id' => $capitalCityBuildingQueue->requested_kingdom,
                'to_kingdom_id' => $kingdom->id,
                'opened' => false,
                'additional_details' => [
                    'messages' => $capitalCityBuildingQueue->messages,
                    'building_data' => $buildingData,
                ],
                'status' => KingdomLogStatusValue::CAPITAL_CITY_BUILDING_REQUEST,
                'published' => true,
            ]);

            $this->updateKingdom->updateKingdomLogs($kingdom->character, true);

            $capitalCityBuildingQueue->delete();

            event(new UpdateCapitalCityBuildingQueueTable($character));
        }
    }


    /**
     * Can we afford, kingdom treasury, the population cost?
     */
    private function canAffordPopulationCost(Kingdom $kingdom, int $populationAmount): bool
    {
        if ($kingdom->treasury <= 0) {
            return false;
        }

        $cost = (new UnitCosts(UnitCosts::PERSON))->fetchCost() * $populationAmount;

        if ($kingdom->treasury < $cost) {
            return false;
        }

        return true;
    }

    /**
     * If we need to request resources, lets send that off.
     *
     * If we do not need resources, we mark as either rejected or ready to build.
     */
    private function processPotentialResourceRequests(CapitalCityBuildingQueue $capitalCityBuildingQueue, Kingdom $kingdom, KingdomBuilding $building, Character $character, array $buildingUpgradeRequest): array
    {
        if (ResourceValidation::shouldRedirectKingdomBuilding($building, $kingdom)) {
            $missingResources = ResourceValidation::getMissingCosts($building, $kingdom);

            if (empty($missingResources)) {
                $buildingUpgradeRequest['secondary_status'] = CapitalCityQueueStatus::BUILDING;

                return $buildingUpgradeRequest;
            }

            $canAffordPopulation = !isset($missingResources['population']) || $missingResources['population'] === 0;

            if (isset($missingResources['population'])) {
                $canAffordPopulation = $this->canAffordPopulationCost($kingdom, $missingResources['population']);
            }

            if (! $canAffordPopulation) {
                $this->messages[] = $building->name.' has been rejected for reason of: Cannot afford to use: '.
                    $kingdom->name.'\'s treasury to purchase an extra: '.
                    $missingResources['population'].' population.';

                $buildingUpgradeRequest['secondary_status'] = CapitalCityQueueStatus::REJECTED;

                return $buildingUpgradeRequest;
            }

            $buildingUpgradeRequest['missing_costs'] = $missingResources;

            $buildingUpgradeRequest['secondary_status'] = CapitalCityQueueStatus::REQUESTING;

            return $buildingUpgradeRequest;
        }

        $buildingUpgradeRequest['secondary_status'] = ($buildingUpgradeRequest['type'] === 'repair' ? CapitalCityQueueStatus::REPAIRING : CapitalCityQueueStatus::BUILDING);

        return $buildingUpgradeRequest;
    }

    private function getKingdomWhoCanAffordCosts(Character $character, array $missingCosts): ?Kingdom {
        return Kingdom::where(function ($query) use ($missingCosts) {
            array_reduce(array_keys($missingCosts), function ($carry, $key) use ($missingCosts, $query) {
                return $query->where('current_' . $key, '>=', $missingCosts[$key]);
            });
        })->first();
    }

    private function createResourceRequest(CapitalCityBuildingQueue $capitalCityBuildingQueue, Kingdom $requestingFromKingdom, Kingdom $kingdomAskingForResources, array $resources): void {

        $pixelDistance = $this->distanceCalculation->calculatePixel($kingdomAskingForResources->x_position, $kingdomAskingForResources->y_position,
            $requestingFromKingdom->x_position, $requestingFromKingdom->y_position);

        $timeToKingdom = $this->distanceCalculation->calculateMinutes($pixelDistance);

        $timeTillFinished = now()->addMinutes($timeToKingdom);
        $startTime = now();

        $resourceRequest = CapitalCityResourceRequest::create([
            'kingdom_requesting_id' => $kingdomAskingForResources->id,
            'request_from_kingdom_id' => $requestingFromKingdom->id,
            'resources' => $resources,
            'started_at' => $startTime,
            'completed_at' => $timeTillFinished,
        ]);

        $capitalCityBuildingQueue->update([
            'started_at' => $startTime,
            'completed_at' => $timeTillFinished,
        ]);

        $capitalCityBuildingQueue = $capitalCityBuildingQueue->refresh();

        $delayJobTime = $timeToKingdom >= 15 ? 15 : $timeTillFinished;

        CapitalCityResourceRequestJob::dispatch($capitalCityBuildingQueue->id, $resourceRequest->id)->delay($delayJobTime);
    }

    private function createUpgradeRequest(Character $character, CapitalCityBuildingQueue $capitalCityBuildingQueue, Kingdom $kingdom, array $buildingsToUpgradeOrRepair) {
        $timeTillFinished = 0;
        $timeToStart = now();

        foreach ($buildingsToUpgradeOrRepair as $buildingRequest) {

            $building = $kingdom->buildings()->find($buildingRequest['building_id']);

            $timeReduction = $building->kingdom->fetchKingBasedSkillValue('building_time_reduction');
            $minutesToRebuild = $building->rebuild_time;

            $minutesToRebuild = $minutesToRebuild - ($minutesToRebuild * $timeReduction);

            $timeToComplete = $timeToStart->clone()->addMinutes($minutesToRebuild);

            $timeTillFinished += $minutesToRebuild;

            $type = BuildingQueueType::UPGRADE;

            if ($buildingRequest['secondary_status'] === CapitalCityQueueStatus::REPAIRING) {
                $type = BuildingQueueType::REPAIR;
            }

            BuildingInQueue::create([
                'character_id' => $character->id,
                'kingdom_id' => $kingdom->id,
                'building_id' => $building->id,
                'to_level' => $buildingRequest['to_level'],
                'paid_with_gold' => false,
                'paid_amount' => 0,
                'completed_at' => $timeToComplete,
                'started_at' => now(),
                'type' => $type,
            ]);
        }

        $totalDelayTime = $timeToStart->clone()->addMinutes($timeTillFinished);

        $capitalCityBuildingQueue->update([
            'start' => $timeToStart,
            'completed_at' => $totalDelayTime,
        ]);

        CapitalCityBuildingRequest::dispatch($capitalCityBuildingQueue->id)->delay(
            $timeTillFinished >= 15 ? $timeToStart->clone->addMinutes(15) : $totalDelayTime
        );
    }
}
