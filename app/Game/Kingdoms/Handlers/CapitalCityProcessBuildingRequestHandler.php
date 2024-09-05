<?php

namespace App\Game\Kingdoms\Handlers;

use App\Flare\Models\BuildingInQueue;
use App\Flare\Models\CapitalCityBuildingQueue;
use App\Flare\Models\CapitalCityResourceRequest;
use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Flare\Models\KingdomBuilding;
use App\Game\Kingdoms\Events\UpdateCapitalCityBuildingQueueTable;
use App\Game\Kingdoms\Jobs\CapitalCityBuildingRequest;
use App\Game\Kingdoms\Jobs\CapitalCityResourceRequest as CapitalCityResourceRequestJob;
use App\Game\Kingdoms\Values\BuildingQueueType;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use App\Game\Kingdoms\Values\CapitalCityResourceRequestType;
use App\Game\Kingdoms\Values\UnitCosts;
use App\Game\Maps\Calculations\DistanceCalculation;
use App\Game\PassiveSkills\Values\PassiveSkillTypeValue;
use Facades\App\Game\Kingdoms\Validation\ResourceValidation;

class CapitalCityProcessBuildingRequestHandler {

    /**
     * @var array $messages
     */
    private array $messages = [];

    /**
     * @param CapitalCityKingdomLogHandler $capitalCityKingdomLogHandler
     * @param DistanceCalculation $distanceCalculation
     */
    public function __construct(
        private readonly CapitalCityKingdomLogHandler $capitalCityKingdomLogHandler,
        private readonly DistanceCalculation $distanceCalculation
    ) {}

    /**
     * Handle the building requests.
     *
     * @param CapitalCityBuildingQueue $capitalCityBuildingQueue
     * @return void
     */
    public function handleBuildingRequests(CapitalCityBuildingQueue $capitalCityBuildingQueue): void
    {
        $requestData = $capitalCityBuildingQueue->building_request_data;
        $kingdom = $capitalCityBuildingQueue->kingdom;
        $character = $capitalCityBuildingQueue->character;

        $requestData = $this->processBuildingRequests($kingdom, $requestData);

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
     * @param Kingdom $kingdom
     * @param array $requestData
     * @return array
     */
    private function processBuildingRequests(
        Kingdom $kingdom,
        array $requestData
    ): array
    {
        foreach ($requestData as $index => $buildingUpgradeRequest) {
            $building = $kingdom->buildings()->where('id', $buildingUpgradeRequest['building_id'])->first();
            $buildingUpgradeRequest = $this->processPotentialResourceRequests(
                $kingdom, $building, $buildingUpgradeRequest
            );

            $requestData[$index] = $buildingUpgradeRequest;
        }

        return $requestData;
    }

    /**
     * Process potential resource requests for building upgrades.
     *
     * @param Kingdom $kingdom
     * @param KingdomBuilding $building
     * @param array $buildingUpgradeRequest
     * @return array
     */
    private function processPotentialResourceRequests(
        Kingdom $kingdom,
        KingdomBuilding $building,
        array $buildingUpgradeRequest
    ): array
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

            if (!$canAffordPopulation) {
                $this->messages[] = $building->name . ' has been rejected for reason of: Cannot afford to use: ' .
                    $kingdom->name . '\'s treasury to purchase an extra: ' .
                    $missingResources['population'] . ' population.';

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
     * Determine if the kingdom can afford the population cost.
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

        return $kingdom->treasury >= $cost;
    }

    /**
     * Calculate the total missing costs.
     *
     * @param array $requestData
     * @return array
     */
    private function calculateSummedMissingCosts(array $requestData): array
    {
        return collect($requestData)
            ->pluck('missing_costs')
            ->map(fn($costs) => collect($costs)->except('population'))
            ->reduce(fn($carry, $costs) => $carry->merge($costs)->map(fn($value, $key) => $carry->get($key, 0) + $value), collect())
            ->toArray();
    }

    /**
     * Handle resource requests if needed.
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
    ): void
    {
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
            $this->createResourceRequest($character, $capitalCityBuildingQueue, $kingdomWhoCanAfford, $kingdom, $summedMissingCosts);
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
        return collect($requestData)
            ->map(fn($item) => array_merge($item, ['secondary_status' => CapitalCityQueueStatus::REJECTED]))
            ->toArray();
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
        $hasBuildingOrRepairing = collect($requestData)->contains(fn($item) => in_array($item['secondary_status'], [
            CapitalCityQueueStatus::BUILDING,
            CapitalCityQueueStatus::REPAIRING,
            CapitalCityQueueStatus::REQUESTING
        ]));

        if (!$hasBuildingOrRepairing) {
            $this->createLogAndTriggerEvents($capitalCityBuildingQueue);
        } else {
            $filteredRequestData = collect($requestData)->filter(fn($item) => in_array($item['secondary_status'], [
                CapitalCityQueueStatus::BUILDING,
                CapitalCityQueueStatus::REPAIRING
            ]))->values()->toArray();

            $this->createUpgradeRequest($capitalCityBuildingQueue->character, $capitalCityBuildingQueue, $capitalCityBuildingQueue->kingdom, $filteredRequestData);
            $this->sendOffEvents($capitalCityBuildingQueue);
        }
    }

    /**
     * Create a log and trigger events if no building or repairing requests are present.
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

        $this->capitalCityKingdomLogHandler->possiblyCreateLogForBuildingQueue($capitalCityBuildingQueue);
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

    /**
     * Create upgrade requests for buildings.
     *
     * @param Character $character
     * @param CapitalCityBuildingQueue $capitalCityBuildingQueue
     * @param Kingdom $kingdom
     * @param array $buildingsToUpgradeOrRepair
     * @return void
     */
    private function createUpgradeRequest(
        Character $character,
        CapitalCityBuildingQueue $capitalCityBuildingQueue,
        Kingdom $kingdom,
        array $buildingsToUpgradeOrRepair
    ): void
    {
        $timeTillFinished = 0;
        $timeToStart = now();

        foreach ($buildingsToUpgradeOrRepair as $buildingRequest) {
            $building = $kingdom->buildings()->find($buildingRequest['building_id']);
            $minutesToRebuild = $building->rebuild_time;
            $timeReduction = $building->kingdom->fetchKingBasedSkillValue('building_time_reduction');
            $minutesToRebuild -= $minutesToRebuild * $timeReduction;

            $timeToComplete = $timeToStart->clone()->addMinutes($minutesToRebuild);
            $timeTillFinished += $minutesToRebuild;
            $type = $buildingRequest['secondary_status'] === CapitalCityQueueStatus::REPAIRING
                ? BuildingQueueType::REPAIR
                : BuildingQueueType::UPGRADE;

            BuildingInQueue::create([
                'character_id' => $character->id,
                'kingdom_id' => $kingdom->id,
                'building_id' => $building->id,
                'to_level' => $buildingRequest['to_level'],
                'paid_with_gold' => false,
                'paid_amount' => 0,
                'completed_at' => $timeToComplete,
                'started_at' => $timeToStart,
                'type' => $type,
            ]);
        }

        $totalDelayTime = $timeToStart->clone()->addMinutes($timeTillFinished);

        $capitalCityBuildingQueue->update([
            'start' => $timeToStart,
            'completed_at' => $totalDelayTime,
        ]);

        CapitalCityBuildingRequest::dispatch($capitalCityBuildingQueue->id)->delay(
            $timeTillFinished >= 15 ? $timeToStart->clone()->addMinutes(15) : $totalDelayTime
        );
    }

    /**
     * Create a resource request.
     *
     * @param Character $character
     * @param CapitalCityBuildingQueue $capitalCityBuildingQueue
     * @param Kingdom $requestingFromKingdom
     * @param Kingdom $kingdomAskingForResources
     * @param array $resources
     * @return void
     */
    private function createResourceRequest(
        Character $character,
        CapitalCityBuildingQueue $capitalCityBuildingQueue,
        Kingdom $requestingFromKingdom,
        Kingdom $kingdomAskingForResources,
        array $resources
    ): void
    {
        $timeToKingdom = $this->getTimeToKingdom($character, $kingdomAskingForResources, $requestingFromKingdom);

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

        $delayJobTime = $timeToKingdom >= 15 ? $startTime->clone()->addMinutes(15) : $timeTillFinished;

        CapitalCityResourceRequestJob::dispatch($capitalCityBuildingQueue->id, $resourceRequest->id, CapitalCityResourceRequestType::BUILDING_QUEUE)->delay($delayJobTime);
    }

    /**
     * Find the first kingdom who can afford the costs.
     *
     * @param Character $character
     * @param array $missingCosts
     * @return Kingdom|null
     */
    private function getKingdomWhoCanAffordCosts(Character $character, array $missingCosts): ?Kingdom {
        return $character->kingdoms()->where(function ($q) use ($missingCosts) {
            foreach ($missingCosts as $resource => $amount) {
                if ($resource !== 'population') {
                    $q->where('current_' . $resource, '>=', $amount);
                }
            }
        })->first();
    }

    private function getTimeToKingdom(Character $character, Kingdom $kingdomAskingForResources, Kingdom $requestingFromKingdom):int {
        $pixelDistance = $this->distanceCalculation->calculatePixel(
            $kingdomAskingForResources->x_position,
            $kingdomAskingForResources->y_position,
            $requestingFromKingdom->x_position,
            $requestingFromKingdom->y_position
        );

        $timeToKingdom = $this->distanceCalculation->calculateMinutes($pixelDistance);

        $skill = $character->passiveSkills->where('passiveSkill.effect_type', PassiveSkillTypeValue::RESOURCE_REQUEST_TIME_REDUCTION)->first();

        $timeToKingdom -= ($timeToKingdom * $skill->resource_request_time_reduction);

        if ($timeToKingdom <= 0) {
            $timeToKingdom = 1;
        }

        return $timeToKingdom;
    }

}
