<?php

namespace App\Game\Kingdoms\Handlers;

use App\Flare\Models\BuildingInQueue;
use App\Flare\Models\CapitalCityBuildingQueue;
use App\Flare\Models\CapitalCityResourceRequest;
use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Flare\Models\KingdomBuilding;
use App\Game\Kingdoms\Jobs\CapitalCityBuildingRequest;
use App\Game\Kingdoms\Jobs\CapitalCityResourceRequest as CapitalCityResourceRequestJob;
use App\Game\Kingdoms\Values\BuildingQueueType;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use App\Game\Kingdoms\Values\UnitCosts;
use Facades\App\Game\Kingdoms\Validation\ResourceValidation;

class CapitalCityProcessBuildingRequestHandler {

    /**
     * @var array $messages
     */
    private array $messages = [];

    public function __construct(private readonly CapitalCityKingdomLogHandler $capitalCityKingdomLogHandler) {}

    /**
     * Handle the building requests.
     *
     * - Either request resources or create building queues.
     * - Process both of these as separate jobs, one or the other. If we must request resources,
     *    we do that before upgrading any buildings even if we have some that can just update.
     *
     * @param CapitalCityBuildingQueue $capitalCityBuildingQueue
     * @return void
     */
    public function handleBuildingRequests(CapitalCityBuildingQueue $capitalCityBuildingQueue): void
    {

        $requestData = $capitalCityBuildingQueue->building_request_data;
        $kingdom = $capitalCityBuildingQueue->kingdom;
        $character = $capitalCityBuildingQueue->character;

        $requestData = $this->processBuildingRequests($capitalCityBuildingQueue, $kingdom, $character, $requestData);

        $summedMissingCosts = $this->calculateSummedMissingCosts($requestData);

        if (!empty($summedMissingCosts)) {
            $this->handleResourceRequests($capitalCityBuildingQueue, $character, $summedMissingCosts, $requestData, $kingdom);
        } else {
            $this->handleNoResourceRequests($capitalCityBuildingQueue, $requestData);
        }
    }

    /**
     * Process each building request.
     *
     * @param CapitalCityBuildingQueue $capitalCityBuildingQueue
     * @param Kingdom $kingdom
     * @param Character $character
     * @param array $requestData
     * @return array
     */
    private function processBuildingRequests(
        CapitalCityBuildingQueue $capitalCityBuildingQueue,
        Kingdom                  $kingdom,
        Character                $character,
        array                    $requestData
    ): array
    {
        foreach ($requestData as $index => $buildingUpgradeRequest) {
            $building = $kingdom->buildings()->where('id', $buildingUpgradeRequest['building_id'])->first();
            $buildingUpgradeRequest = $this->processPotentialResourceRequests(
                $kingdom, $building, $buildingUpgradeRequest
            );

            $capitalCityBuildingQueue->update(['building_request_data' => $requestData]);

            if ($buildingUpgradeRequest['secondary_status'] === CapitalCityQueueStatus::REQUESTING ||
                $buildingUpgradeRequest['secondary_status'] === CapitalCityQueueStatus::CANCELLED) {
                continue;
            }

            $requestData[$index] = $buildingUpgradeRequest;
        }

        return $requestData;
    }

    private function processPotentialResourceRequests(Kingdom $kingdom, KingdomBuilding $building, array $buildingUpgradeRequest): array
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

    /**
     * Can we afford the population cost?
     *
     * @param Kingdom $kingdom
     * @param int $populationAmount
     * @return bool
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
     * Calculate the summed missing costs.
     *
     * @param array $requestData
     * @return array
     */
    private function calculateSummedMissingCosts(array $requestData): array
    {
        $requestDataCollection = collect($requestData);

        return $requestDataCollection->pluck('missing_costs')->map(function ($costs) {
            return collect($costs)->except('population');
        })->reduce(function ($carry, $costs) {
            return $carry->merge($costs)->map(fn($value, $key) => ($carry->get($key, 0) + $value));
        }, collect())->toArray();
    }

    /**
     * Handle the case where resource requests are needed.
     *
     * @param CapitalCityBuildingQueue $capitalCityBuildingQueue
     * @param Character $character
     * @param array $summedMissingCosts
     * @param array $requestData
     * @param Kingdom $kingdom
     * @return void
     */
    private function handleResourceRequests(
        CapitalCityBuildingQueue $capitalCityBuildingQueue,
        Character $character,
        array $summedMissingCosts,
        array $requestData,
        Kingdom $kingdom
    ): void {
        $kingdomWhoCanAfford = $this->getKingdomWhoCanAffordCosts($character, $summedMissingCosts);

        if (is_null($kingdomWhoCanAfford)) {
            $requestData = $this->markRequestsAsRejected($requestData);

            $this->messages[] = 'No kingdom could be found to request the resources for these buildings.';
        }

        $capitalCityBuildingQueue->update([
            'building_request_data' => $requestData,
            'messages' => $this->messages,
        ]);

        $capitalCityBuildingQueue = $capitalCityBuildingQueue->refresh();

        if (empty($this->messages)) {
            $this->createResourceRequest($capitalCityBuildingQueue, $kingdomWhoCanAfford, $kingdom, $summedMissingCosts);
        }

        $this->sendOffEvents($capitalCityBuildingQueue);
    }

    /**
     * Mark all requests as rejected where secondary status is REQUESTING.
     *
     * @param array $requestData
     * @return array
     */
    private function markRequestsAsRejected(array $requestData): array
    {
        return collect($requestData)->map(function ($item) {
            $item['secondary_status'] = CapitalCityQueueStatus::REJECTED;

            return $item;
        })->toArray();
    }

    /**
     * Handle the case where no resource requests are needed.
     *
     * @param CapitalCityBuildingQueue $capitalCityBuildingQueue
     * @param array $requestData
     * @return void
     */
    private function handleNoResourceRequests(CapitalCityBuildingQueue $capitalCityBuildingQueue, array $requestData): void
    {
        $hasBuildingOrRepairing = collect($requestData)->contains(function ($item) {
            return in_array($item['secondary_status'], [CapitalCityQueueStatus::BUILDING, CapitalCityQueueStatus::REPAIRING, CapitalCityQueueStatus::REQUESTING]);
        });

        if (!$hasBuildingOrRepairing) {
            $this->createLogAndTriggerEvents($capitalCityBuildingQueue);
        } else {
            $filteredRequestData = collect($requestData)->filter(function ($item) {
                return in_array($item['secondary_status'], [CapitalCityQueueStatus::BUILDING, CapitalCityQueueStatus::REPAIRING]);
            })->values()->toArray();

            $this->createUpgradeRequest($capitalCityBuildingQueue->character, $capitalCityBuildingQueue, $capitalCityBuildingQueue->kingdom, $filteredRequestData);
            $this->sendOffEvents($capitalCityBuildingQueue);
        }
    }

    /**
     * Create log and trigger events if no building or repairing requests are present.
     *
     * @param CapitalCityBuildingQueue $capitalCityBuildingQueue
     * @return void
     */
    private function createLogAndTriggerEvents(CapitalCityBuildingQueue $capitalCityBuildingQueue): void
    {
        $capitalCityBuildingQueue->update([
            'building_request_data' => $capitalCityBuildingQueue->building_request_data,
            'messages' => $this->messages,
        ]);

        $capitalCityBuildingQueue = $capitalCityBuildingQueue->refresh();

        $this->capitalCityKingdomLogHandler->possiblyCreateLogForQueue($capitalCityBuildingQueue);
        $this->sendOffEvents($capitalCityBuildingQueue);
    }

    /**
     * Refresh queue and trigger events.
     *
     * @param CapitalCityBuildingQueue $capitalCityBuildingQueue
     * @return void
     */
    private function sendOffEvents(CapitalCityBuildingQueue $capitalCityBuildingQueue): void
    {
        event(new UpdateCapitalCityBuildingQueueTable($capitalCityBuildingQueue->character));
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

}
