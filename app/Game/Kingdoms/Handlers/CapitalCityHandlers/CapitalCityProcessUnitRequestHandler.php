<?php

namespace App\Game\Kingdoms\Handlers\CapitalCityHandlers;

use App\Flare\Models\CapitalCityResourceRequest;
use App\Flare\Models\CapitalCityUnitQueue;
use App\Flare\Models\Character;
use App\Flare\Models\GameBuildingUnit;
use App\Flare\Models\GameUnit;
use App\Flare\Models\Kingdom;
use App\Flare\Models\KingdomBuilding;
use App\Flare\Models\KingdomUnit;
use App\Game\Kingdoms\Events\UpdateCapitalCityUnitQueueTable;
use App\Game\Kingdoms\Jobs\CapitalCityResourceRequest as CapitalCityResourceRequestJob;
use App\Game\Kingdoms\Jobs\CapitalCityUnitRequest;
use App\Game\Kingdoms\Service\UnitService;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use App\Game\Kingdoms\Values\CapitalCityResourceRequestType;
use App\Game\Kingdoms\Values\UnitCosts;
use App\Game\Maps\Calculations\DistanceCalculation;
use App\Game\PassiveSkills\Values\PassiveSkillTypeValue;
use Facades\App\Game\Kingdoms\Validation\ResourceValidation;


class CapitalCityProcessUnitRequestHandler {

    const MAX_DAYS = 7;

    /**
     * @var array $messages
     */
    private array $messages = [];

    /**
     * @param CapitalCityKingdomLogHandler $capitalCityKingdomLogHandler
     * @param CapitalCityRequestResourcesHandler $capitalCityRequestResourcesHandler
     * @param DistanceCalculation $distanceCalculation
     * @param UnitService $unitService
     */
    public function __construct(
        private readonly CapitalCityKingdomLogHandler $capitalCityKingdomLogHandler,
        private readonly CapitalCityRequestResourcesHandler $capitalCityRequestResourcesHandler,
        private readonly DistanceCalculation $distanceCalculation,
        private readonly UnitService $unitService
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
            $gameUnit = GameUnit::where('name', $unitRequest['name'])->first();
            $gameBuildingRelation = GameBuildingUnit::where('game_unit_id', $gameUnit->id)->first();
            $building = $kingdom->buildings()->where('game_building_id', $gameBuildingRelation->game_building_id)->first();

            if ($this->isBuildingLocked($building, $kingdom)) {
                $requestData[$index]['secondary_status'] = CapitalCityQueueStatus::REJECTED;

                continue;
            }

            if ($this->isBuildingUnderLeveled($building, $gameBuildingRelation, $kingdom)) {
                $requestData[$index]['secondary_status'] = CapitalCityQueueStatus::REJECTED;

                continue;
            }

            if ($this->isTimeGreaterThanSevenDays($kingdom->character, $kingdom, $gameUnit, $unitRequest['amount'])) {
                $requestData[$index]['secondary_status'] = CapitalCityQueueStatus::REJECTED;

                continue;
            }

            $unitRequest = $this->processPotentialResourceRequests($kingdom, $gameUnit, $unitRequest);

            $requestData[$index] = $unitRequest;
        }

        return $requestData;
    }

    private function isBuildingLocked(KingdomBuilding $building, Kingdom $kingdom): bool {
        if ($building->is_locked) {

            $this->messages[] = 'Building is locked in '.$kingdom->name.'. You need to unlock the building: '.$building->name.' first by leveling a passive of the same name to level 1.';

            return true;
        }

        return false;
    }

    private function isBuildingUnderLeveled(KingdomBuilding $building, GameBuildingUnit $gameBuildingRelation, Kingdom $kingdom): bool {
        if ($building->level < $gameBuildingRelation->required_level) {

            $this->messages[] = 'Building is under level in '.$kingdom->name.'. You need to level the building: '.$building->name.' to level: '.$gameBuildingRelation->required_level.' first.';

            return true;
        }

        return false;
    }

    /**
     * Process potential resource requests for unit recruitment.
     *
     * @param Kingdom $kingdom
     * @param KingdomUnit $unit
     * @param array $unitRequest
     * @return array
     */
    private function processPotentialResourceRequests(Kingdom $kingdom, GameUnit $unit, array $unitRequest): array
    {
        $missingResources = ResourceValidation::getMissingUnitResources($unit, $kingdom, $unitRequest['amount']);

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
        $this->capitalCityRequestResourcesHandler->handleResourceRequests(
            $capitalCityUnitQueue,
            $character,
            $missingResources,
            $requestData,
            $kingdom
        );
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
     * Create a log and trigger relevant events.
     *
     * @param CapitalCityUnitQueue $capitalCityUnitQueue
     * @return void
     */
    private function logAndTriggerEvents(CapitalCityUnitQueue $capitalCityUnitQueue): void
    {
        $this->capitalCityKingdomLogHandler->possiblyCreateLogForUnitQueue($capitalCityUnitQueue);
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
     * @param array $requestData
     * @return void
     */
    private function createUnitRecruitmentRequest(
        Character $character,
        CapitalCityUnitQueue $capitalCityUnitQueue,
        array $requestData
    ): void {

        $hasBuildingOrRepairing = collect($requestData)->contains(fn($item) => in_array($item['secondary_status'], [
            CapitalCityQueueStatus::RECRUITING,
            CapitalCityQueueStatus::REQUESTING
        ]));

        if (!$hasBuildingOrRepairing) {
            $this->createLogAndTriggerEvents($capitalCityUnitQueue);
        } else {
            $filteredRequestData = collect($requestData)->filter(fn($item) => in_array($item['secondary_status'], [
                CapitalCityQueueStatus::RECRUITING,
            ]))->values()->toArray();

            dump($filteredRequestData);

//            $this->createUpgradeRequest($character, $capitalCityUnitQueue, $capitalCityUnitQueue->kingdom, $filteredRequestData);
//            $this->sendOffEvents($capitalCityUnitQueue);
        }
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

    private function getTimeForRecruitment(Character $character, GameUnit $gameUnit, int $amount): int
    {
        $totalTime = $gameUnit->time_to_recruit * $amount;

        return $totalTime - $totalTime * $this->unitService->fetchTimeReduction($character)->unit_time_reduction;
    }

    private function isTimeGreaterThanSevenDays(Character $character, Kingdom $kingdom, GameUnit $gameUnit, int $amount): bool
    {
        $timeTillDone = $this->getTimeForRecruitment($character, $gameUnit, $amount);

        $timeTillDone = now()->addSeconds($timeTillDone);

        if (now()->diffInDays($timeTillDone) > self::MAX_DAYS) {
            $this->messages[] = $gameUnit->name.' for kingdom: '.$kingdom->name.' would take longer then 7 (Real World) Days. The kingdom has rejected this recruitment order. If you want this amount of units, you must recruit it from the kingdom it\'s self.';

            return true;
        }

        return false;
    }
}
