<?php

namespace App\Game\Kingdoms\Handlers;

use App\Flare\Models\CapitalCityResourceRequest;
use App\Flare\Models\UnitInQueue;
use App\Flare\Models\CapitalCityUnitQueue;
use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Flare\Models\KingdomUnit;
use App\Game\Kingdoms\Events\UpdateCapitalCityUnitQueueTable;
use App\Game\Kingdoms\Jobs\CapitalCityResourceRequest as CapitalCityResourceRequestJob;
use App\Game\Kingdoms\Jobs\CapitalCityUnitRequest;
use App\Game\Kingdoms\Values\CapitalCityResourceRequestType;
use App\Game\Kingdoms\Values\UnitCosts;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use App\Game\Maps\Calculations\DistanceCalculation;
use App\Game\PassiveSkills\Values\PassiveSkillTypeValue;
use Facades\App\Game\Kingdoms\Validation\ResourceValidation;

class CapitalCityProcessUnitRequestHandler {

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
     * Public method to handle unit requests.
     *
     * @param CapitalCityUnitQueue $capitalCityUnitQueue
     * @return void
     */
    public function handleUnitRequests(CapitalCityUnitQueue $capitalCityUnitQueue): void
    {
        $requestData = $capitalCityUnitQueue->unit_request_data;
        $kingdom = $capitalCityUnitQueue->kingdom;
        $character = $capitalCityUnitQueue->character;

        $requestData = $this->processUnitRequests($kingdom, $requestData);
        $missingResources = $this->calculateMissingResources($requestData);

        if (!empty($missingResources)) {
            $this->handleResourceRequests($capitalCityUnitQueue, $character, $missingResources, $requestData, $kingdom);
        } else {
            $this->handleNoResourceRequests($capitalCityUnitQueue, $requestData);
        }
    }

    /**
     * Process each unit request.
     *
     * @param Kingdom $kingdom
     * @param array $requestData
     * @return array
     */
    private function processUnitRequests(Kingdom $kingdom, array $requestData): array
    {
        foreach ($requestData as $index => $unitRequest) {
            $unit = $kingdom->units()->where('id', $unitRequest['unit_id'])->first();
            $unitRequest = $this->processPotentialResourceRequests($kingdom, $unit, $unitRequest);
            $requestData[$index] = $unitRequest;
        }

        return $requestData;
    }

    /**
     * Process potential resource requests for unit recruitment.
     *
     * @param Kingdom $kingdom
     * @param KingdomUnit $unit
     * @param array $unitRequest
     * @return array
     */
    private function processPotentialResourceRequests(Kingdom $kingdom, KingdomUnit $unit, array $unitRequest): array
    {
        $missingResources = ResourceValidation::getMissingCosts($unit, $kingdom);

        if (!empty($missingResources)) {
            if (!$this->canAffordPopulationCost($kingdom, $missingResources['population'] ?? 0)) {
                $this->messages[] = "Unit recruitment for {$unit->name} rejected due to insufficient population.";
                $unitRequest['secondary_status'] = CapitalCityQueueStatus::REJECTED;
                return $unitRequest;
            }

            $unitRequest['missing_costs'] = $missingResources;
            $unitRequest['secondary_status'] = CapitalCityQueueStatus::REQUESTING;
        } else {
            $unitRequest['secondary_status'] = CapitalCityQueueStatus::RECRUITING;
        }

        return $unitRequest;
    }

    /**
     * Check if kingdom can afford the population cost.
     *
     * @param Kingdom $kingdom
     * @param int $populationAmount
     * @return bool
     */
    private function canAffordPopulationCost(Kingdom $kingdom, int $populationAmount): bool
    {
        if ($populationAmount <= 0) {
            return true;
        }

        $cost = (new UnitCosts(UnitCosts::PERSON))->fetchCost() * $populationAmount;

        return $kingdom->treasury >= $cost;
    }

    /**
     * Calculate the missing resources for all unit requests.
     *
     * @param array $requestData
     * @return array
     */
    private function calculateMissingResources(array $requestData): array
    {
        return collect($requestData)
            ->pluck('missing_costs')
            ->reduce(fn($carry, $costs) => $carry->merge($costs)->map(fn($value, $key) => $carry->get($key, 0) + $value), collect())
            ->toArray();
    }

    /**
     * Handle resource requests for unit recruitment.
     *
     * @param CapitalCityUnitQueue $capitalCityUnitQueue
     * @param Character $character
     * @param array $missingResources
     * @param array $requestData
     * @param Kingdom $kingdom
     * @return void
     */
    private function handleResourceRequests(
        CapitalCityUnitQueue $capitalCityUnitQueue,
        Character $character,
        array $missingResources,
        array $requestData,
        Kingdom $kingdom
    ): void {
        $resourceProvidingKingdom = $this->findKingdomToProvideResources($character, $missingResources);

        if (is_null($resourceProvidingKingdom)) {
            $requestData = $this->markRequestsAsRejected($requestData);
            $this->messages[] = 'No kingdom could provide the resources for unit recruitment.';
        }

        $this->updateQueue($capitalCityUnitQueue, $requestData);

        if (empty($this->messages)) {
            $this->createResourceRequest($character, $capitalCityUnitQueue, $resourceProvidingKingdom, $kingdom, $missingResources);
        }

        $this->triggerEvents($capitalCityUnitQueue);
    }

    /**
     * Handle the case where no resource requests are needed.
     *
     * @param CapitalCityUnitQueue $capitalCityUnitQueue
     * @param array $requestData
     * @return void
     */
    private function handleNoResourceRequests(CapitalCityUnitQueue $capitalCityUnitQueue, array $requestData): void
    {
        if ($this->hasRecruitingUnits($requestData)) {
            $this->createUnitRecruitmentRequest($capitalCityUnitQueue->character, $capitalCityUnitQueue, $capitalCityUnitQueue->kingdom, $requestData);
        } else {
            $this->logAndTriggerEvents($capitalCityUnitQueue);
        }
    }

    /**
     * Check if there are any recruiting units.
     *
     * @param array $requestData
     * @return bool
     */
    private function hasRecruitingUnits(array $requestData): bool
    {
        return collect($requestData)
            ->contains(fn($item) => $item['secondary_status'] === CapitalCityQueueStatus::RECRUITING);
    }

    /**
     * Update the unit queue.
     *
     * @param CapitalCityUnitQueue $capitalCityUnitQueue
     * @param array $requestData
     * @return void
     */
    private function updateQueue(CapitalCityUnitQueue $capitalCityUnitQueue, array $requestData): void
    {
        $capitalCityUnitQueue->update([
            'unit_request_data' => $requestData,
            'messages' => $this->messages,
        ]);
    }

    /**
     * Find a kingdom that can provide the necessary resources.
     *
     * @param Character $character
     * @param array $missingResources
     * @return Kingdom|null
     */
    private function findKingdomToProvideResources(Character $character, array $missingResources): ?Kingdom
    {
        return $character->kingdoms()
            ->where(function ($query) use ($missingResources) {
                foreach ($missingResources as $resource => $amount) {
                    $query->where('current_' . $resource, '>=', $amount);
                }
            })
            ->first();
    }

    /**
     * Create a log and trigger relevant events.
     *
     * @param CapitalCityUnitQueue $capitalCityUnitQueue
     * @return void
     */
    private function logAndTriggerEvents(CapitalCityUnitQueue $capitalCityUnitQueue): void
    {
        $this->capitalCityKingdomLogHandler->possiblyCreateLogForQueue($capitalCityUnitQueue);
        $this->triggerEvents($capitalCityUnitQueue);
    }

    /**
     * Trigger events for the unit queue.
     *
     * @param CapitalCityUnitQueue $capitalCityUnitQueue
     * @return void
     */
    private function triggerEvents(CapitalCityUnitQueue $capitalCityUnitQueue): void
    {
        event(new UpdateCapitalCityUnitQueueTable($capitalCityUnitQueue->character->refresh()));
    }

    /**
     * Create a unit recruitment request.
     *
     * @param Character $character
     * @param CapitalCityUnitQueue $capitalCityUnitQueue
     * @param Kingdom $kingdom
     * @param array $requestData
     * @return void
     */
    private function createUnitRecruitmentRequest(
        Character $character,
        CapitalCityUnitQueue $capitalCityUnitQueue,
        Kingdom $kingdom,
        array $requestData
    ): void {
        $unitQueue = UnitInQueue::create([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'units_in_queue' => $this->prepareUnitsInQueue($requestData),
        ]);

        CapitalCityUnitRequest::dispatch($unitQueue)->onQueue('capital_city_unit');
    }

    /**
     * Prepare units for the queue.
     *
     * @param array $requestData
     * @return array
     */
    private function prepareUnitsInQueue(array $requestData): array
    {
        return collect($requestData)
            ->filter(fn($request) => $request['secondary_status'] === CapitalCityQueueStatus::RECRUITING)
            ->map(fn($request) => ['unit_id' => $request['unit_id'], 'amount' => $request['amount']])
            ->toArray();
    }

    /**
     * Mark all unit requests as rejected.
     *
     * @param array $requestData
     * @return array
     */
    private function markRequestsAsRejected(array $requestData): array
    {
        return collect($requestData)
            ->map(function ($unitRequest) {
                $unitRequest['secondary_status'] = CapitalCityQueueStatus::REJECTED;
                return $unitRequest;
            })
            ->toArray();
    }

    /**
     * Create a resource request for recruitment.
     *
     * @param Character $character
     * @param CapitalCityUnitQueue $capitalCityUnitQueue
     * @param Kingdom $providingKingdom
     * @param Kingdom $requestingKingdom
     * @param array $missingResources
     * @return void
     */
    private function createResourceRequest(
        Character $character,
        CapitalCityUnitQueue $capitalCityUnitQueue,
        Kingdom $providingKingdom,
        Kingdom $requestingKingdom,
        array $missingResources
    ): void {
        $timeToKingdom = $this->getTimeToKingdom($character, $requestingKingdom, $providingKingdom);

        $timeTillFinished = now()->addMinutes($timeToKingdom);
        $startTime = now();

        $resourceRequest = CapitalCityResourceRequest::create([
            'kingdom_requesting_id' => $requestingKingdom->id,
            'request_from_kingdom_id' => $providingKingdom->id,
            'resources' => $missingResources,
            'started_at' => $startTime,
            'completed_at' => $timeTillFinished,
        ]);

        $capitalCityUnitQueue->update([
            'started_at' => $startTime,
            'completed_at' => $timeTillFinished,
        ]);

        $capitalCityUnitQueue = $capitalCityUnitQueue->refresh();

        $delayJobTime = $timeToKingdom >= 15 ? $startTime->clone()->addMinutes(15) : $timeTillFinished;

        CapitalCityResourceRequestJob::dispatch($capitalCityUnitQueue->id, $resourceRequest->id, CapitalCityResourceRequestType::UNIT_QUEUE)->delay($delayJobTime);
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
