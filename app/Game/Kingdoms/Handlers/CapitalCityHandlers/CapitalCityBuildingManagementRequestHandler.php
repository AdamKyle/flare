<?php

namespace App\Game\Kingdoms\Handlers\CapitalCityHandlers;

use App\Flare\Models\CapitalCityBuildingQueue;
use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Flare\Models\KingdomBuilding;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Kingdoms\Events\UpdateCapitalCityBuildingQueueTable;
use App\Game\Kingdoms\Events\UpdateCapitalCityBuildingUpgrades;
use App\Game\Kingdoms\Jobs\CapitalCityBuildingRequestMovement;
use App\Game\Kingdoms\Service\KingdomBuildingService;
use App\Game\Kingdoms\Service\UnitMovementService;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use App\Game\PassiveSkills\Values\PassiveSkillTypeValue;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CapitalCityBuildingManagementRequestHandler {

    use ResponseBuilder;

    /**
     * @param KingdomBuildingService $kingdomBuildingService
     * @param UnitMovementService $unitMovementService
     */
    public function __construct(
        private readonly KingdomBuildingService $kingdomBuildingService,
        private readonly UnitMovementService $unitMovementService,
    ) {}

    /**
     * Create the requests based on the kingdoms and their requests.
     *
     * Each request is an object of kingdom id and an array of building ids.
     *
     * @param Character $character
     * @param Kingdom $kingdom
     * @param array $requests
     * @param string $type
     * @return array
     */
    public function createRequestQueue(Character $character, Kingdom $kingdom, array $requests, string $type): array {

        $currentTime = now();

        collect($requests)->each(function (array $request) use ($character, $kingdom, $type, $currentTime) {
            $kingdomId = $request['kingdomId'];
            $buildingIds = $request['buildingIds'];

            $buildings = $this->getBuildingsForRequest($kingdomId, $buildingIds);
            $toKingdom = $character->kingdoms->find($kingdomId);
            $timeNeeded = $this->calculateTravelTime($character, $toKingdom, $kingdom->id);

            $buildingQueueData = $this->buildQueueData($buildings, $type);

            $travelTimeNeeded = $currentTime->clone()->addMinutes($timeNeeded);

            $capitalCityBuildingQueue = CapitalCityBuildingQueue::create([
                'kingdom_id' => $kingdomId,
                'requested_kingdom' => $kingdom->id,
                'building_request_data' => $buildingQueueData,
                'character_id' => $character->id,
                'status' => CapitalCityQueueStatus::TRAVELING,
                'started_at' => $currentTime,
                'completed_at' => $travelTimeNeeded
            ]);

            $dispatchTime = $travelTimeNeeded;

            if ($timeNeeded >= 15) {
                $dispatchTime = $currentTime->clone()->addMinutes(15);
            }

            $this->dispatchQueueMovement($capitalCityBuildingQueue, $dispatchTime);
            $this->sendOffEvents($character, $kingdom);

        });

        return $this->successResult([
            'message' => 'Building upgrades have been sent off to their respective kingdoms.
            The list below has been updated to reflect kingdoms you can send upgrade requests to. If
            you click: "Building Upgrade/Repair" in the top right, you will see a table of orders and
            their associated statuses.',
        ]);
    }

    /**
     * Find buildings for a kingdom based off the array of ids.
     *
     * @param int $kingdomId
     * @param array $buildingIds
     * @return Collection
     */
    private function getBuildingsForRequest(int $kingdomId, array $buildingIds): Collection {
        return KingdomBuilding::where('kingdom_id', $kingdomId)->whereIn('id', $buildingIds)->get();
    }

    /**
     * Calculate the time required for unit movement.
     *
     * @param Character $character The character initiating the request.
     * @param Kingdom $toKingdom The target kingdom for the movement.
     * @param int $kingdomId The ID of the originating kingdom.
     *
     * @return int The time required for the movement in minutes.
     */
    private function calculateTravelTime(Character $character, Kingdom $toKingdom, int $kingdomId): int
    {
        return $this->unitMovementService->determineTimeRequired(
            $character, $toKingdom, $kingdomId, PassiveSkillTypeValue::CAPITAL_CITY_REQUEST_BUILD_TRAVEL_TIME_REDUCTION
        );
    }

    /**
     * Build the queue data for creating a new building upgrade request.
     *
     * @param Collection $buildings The collection of buildings to be upgraded.
     * @param string $type The type of request ('upgrade' or otherwise).
     *
     * @return array The queue data for the building upgrade request.
     */
    private function buildQueueData(Collection $buildings, string $type): array
    {
        return $buildings->map(function ($building) use ($type) {
            $fromLevel = $type === 'upgrade' ? $building->level : null;
            $toLevel = $type === 'upgrade' ? $building->level + 1 : null;

            return [
                'building_id' => $building->id,
                'costs' => $this->kingdomBuildingService->getBuildingCosts($building),
                'type' => $type,
                'missing_costs' => [],
                'secondary_status' => null,
                'from_level' => $fromLevel,
                'to_level' => $toLevel,
            ];
        })->toArray();
    }

    /**
     * Dispatch the queue movement job.
     *
     * @param CapitalCityBuildingQueue $queue The created building queue.
     * @param Carbon $dispatchTime
     * @return void
     */
    private function dispatchQueueMovement(CapitalCityBuildingQueue $queue, Carbon $dispatchTime): void
    {
        CapitalCityBuildingRequestMovement::dispatch($queue->id)->delay($dispatchTime);
    }

    /**
     * Trigger events for updating capital city building upgrades and queue table.
     *
     * @param Character $character The character initiating the request.
     * @param Kingdom $kingdom The kingdom from which the request is made.
     *
     * @return void
     */
    private function sendOffEvents(Character $character, Kingdom $kingdom): void
    {
        event(new UpdateCapitalCityBuildingUpgrades($character, $kingdom));
        event(new UpdateCapitalCityBuildingQueueTable($character, $kingdom));
    }

}
