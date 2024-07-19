<?php

namespace App\Game\Kingdoms\Service;

use App\Flare\Models\BuildingInQueue;
use App\Flare\Models\CapitalCityBuildingCancellation;
use App\Flare\Models\CapitalCityBuildingQueue;
use App\Flare\Models\CapitalCityUnitCancellation;
use App\Flare\Models\CapitalCityUnitQueue;
use App\Flare\Models\Character;
use App\Flare\Models\GameUnit;
use App\Flare\Models\Kingdom;
use App\Flare\Models\KingdomBuilding;
use App\Flare\Models\UnitInQueue;
use App\Flare\Transformers\KingdomBuildingTransformer;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection as SupportCollection;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;

class CapitalCityManagementService
{
    use ResponseBuilder;

    public function __construct(
        private readonly UpdateKingdom $updateKingdom,
        private readonly CapitalCityBuildingManagement $capitalCityBuildingManagement,
        private readonly CapitalCityUnitManagement $capitalCityUnitManagement,
        private readonly KingdomBuildingTransformer $kingdomBuildingTransformer,
        private readonly UnitMovementService $unitMovementService,
        private readonly Manager $manager
    ) {}

    /**
     * Make the current kingdom a capital city.
     *
     * @param Kingdom $kingdom
     * @return array
     */
    public function makeCapitalCity(Kingdom $kingdom): array
    {
        $this->validateOneCapitalCityPerPlane($kingdom);

        $kingdom->update(['is_capital' => true]);
        $this->updateKingdom($kingdom);

        return $this->successResult([
            'message' => $this->getCapitalCityMessage($kingdom),
        ]);
    }

    /**
     * Fetch buildings from other kingdoms for upgrades or repairs.
     *
     * @param Character $character
     * @param Kingdom $kingdom
     * @param bool $returnArray
     * @return array
     */
    public function fetchBuildingsForUpgradesOrRepairs(Character $character, Kingdom $kingdom, bool $returnArray = false): array
    {
        $kingdoms = $this->getOtherKingdoms($character, $kingdom);
        $kingdomBuildingData = $this->fetchBuildingsData($kingdoms);

        return $returnArray ? $kingdomBuildingData : $this->successResult($kingdomBuildingData);
    }

    /**
     * Fetch kingdoms for selection.
     *
     * @param Kingdom $kingdom
     * @return array
     */
    public function fetchKingdomsForSelection(Kingdom $kingdom): array
    {
        $kingdoms = $this->getSelectableKingdoms($kingdom);
        return $this->successResult(['kingdoms' => $kingdoms]);
    }

    /**
     * Walk all kingdoms for the character.
     *
     * @param Character $character
     * @param Kingdom $kingdom
     * @return array
     */
    public function walkAllKingdoms(Character $character, Kingdom $kingdom): array
    {
        $this->updateWalkedKingdoms($character, $kingdom);
        $this->updateKingdom($kingdom);

        return $this->successResult(['message' => 'All kingdoms walked!']);
    }

    /**
     * Send off building upgrade or repair requests.
     *
     * @param Character $character
     * @param Kingdom $kingdom
     * @param array $params
     * @param string $type
     * @return array
     */
    public function sendoffBuildingRequests(Character $character, Kingdom $kingdom, array $params, string $type): array
    {
        return $this->capitalCityBuildingManagement->createBuildingUpgradeRequestQueue($character, $kingdom, $params, $type);
    }

    /**
     * @param Character $character
     * @param Kingdom $kingdom
     * @param array $requestData
     * @return array
     */
    public function sendOffUnitRecruitmentOrders(Character $character, Kingdom $kingdom, array $requestData): array {
        return $this->capitalCityUnitManagement->createUnitRequests($character, $kingdom, $requestData);
    }

    /**
     * Fetch the building queue data.
     *
     * @param Character $character
     * @param Kingdom|null $kingdom
     * @return array
     */
    public function fetchBuildingQueueData(Character $character, Kingdom $kingdom = null): array {

        $queues = CapitalCityBuildingQueue::where('character_id', $character->id);

        if (!is_null($kingdom)) {
            $queues = $queues->where('kingdom_id', '!=', $kingdom->id);
        }

        $queues = $queues->get();

        $data = [];

        foreach ($queues as $queue) {
            $kingdom = $queue->kingdom;

            $end = Carbon::parse($queue->completed_at)->timestamp;
            $current = Carbon::now()->timestamp;

            $timeLeftInSeconds = 0;

            if (!now()->gt($queue->completed_at)) {
                $timeLeftInSeconds = $end - $current;
            }

            $buildingRequestData = $queue->building_request_data;

            foreach ($buildingRequestData as $buildingRequest) {

                $building = KingdomBuilding::where('kingdom_id', $kingdom->id)->where('id', $buildingRequest['building_id'])->first();

                if ($buildingRequest['secondary_status'] === CapitalCityQueueStatus::BUILDING || $buildingRequest['secondary_status'] === CapitalCityQueueStatus::REPAIRING) {
                    $buildingQueue = BuildingInQueue::where('building_id', $buildingRequest['building_id'])->where('kingdom_id', $kingdom->id)->first();

                    if (!is_null($buildingQueue)) {
                        $end = Carbon::parse($buildingQueue->completed_at)->timestamp;
                        $current = Carbon::now()->timestamp;

                        $timeLeftInSeconds = $end - $current;
                    }
                }


                $queueData = [
                    'kingdom_name' => $kingdom->name . '(X/Y: '.$kingdom->x_position.'/'.$kingdom->y_position.')',
                    'status' => $queue->status,
                    'messages' => $queue->messages,
                    'time_left_seconds' => $timeLeftInSeconds > 0 ? $timeLeftInSeconds : 0,
                    'building_name' => $building->name,
                    'secondary_status' => $buildingRequest['secondary_status'],
                    'kingdom_id' => $kingdom->id,
                    'building_id' => $building->id,
                    'queue_id' => $queue->id,
                    'is_cancel_request' => false,
                ];

                $data[] = $queueData;
            }
        }

        return array_values(collect(array_merge($this->fetchBuildingCancellationQueueData($character), $data))->sortByDesc('time_left_seconds')->sortByDesc('is_cancel_request')->toArray());
    }

    /**
     * Fetch Unit Queue Data.
     *
     * @param Character $character
     * @param Kingdom|null $kingdom
     * @return array
     */
    public function fetchUnitQueueData(Character $character, Kingdom $kingdom = null): array {

        $queues = CapitalCityUnitQueue::where('character_id', $character->id);

        if (!is_null($kingdom)) {
            $queues = $queues->where('kingdom_id', '!=', $kingdom->id);
        }

        $queues = $queues->get();

        $data = [];

        foreach ($queues as $queue) {
            $kingdom = $queue->kingdom;

            $end = Carbon::parse($queue->completed_at)->timestamp;
            $current = Carbon::now()->timestamp;

            $timeLeftInSeconds = 0;

            if (!now()->gt($queue->completed_at)) {
                $timeLeftInSeconds = $end - $current;
            }

            $unitRequestData = $queue->unit_request_data;

            foreach ($unitRequestData as $unitRequest) {

                if ($unitRequest['secondary_status'] === CapitalCityQueueStatus::RECRUITING) {
                    $gameUnit = GameUnit::where('name', $unitRequest['name'])->first();

                    $unitInQueue = UnitInQueue::where('game_unit_id', $gameUnit->id)->where('kingdom_id', $kingdom->id)->first();

                    if (is_null($unitInQueue)) {
                        continue;
                    }

                    $end = Carbon::parse($unitInQueue->completed_at)->timestamp;
                    $current = Carbon::now()->timestamp;

                    $timeLeftInSeconds = $end - $current;
                }


                if ($unitRequest['secondary_status'] === CapitalCityQueueStatus::REJECTED ||
                    $unitRequest['secondary_status'] === CapitalCityQueueStatus::FINISHED ||
                    $unitRequest['secondary_status'] === CapitalCityQueueStatus::CANCELLED
                ) {
                    $timeLeftInSeconds = 0;
                }

                $gameUnit = GameUnit::where('name', $unitRequest['name'])->first();

                $queueData = [
                    'kingdom_name' => $kingdom->name . '(X/Y: '.$kingdom->x_position.'/'.$kingdom->y_position.')',
                    'status' => $queue->status,
                    'time_left_seconds' => $timeLeftInSeconds > 0 ? $timeLeftInSeconds : 0,
                    'unit_name' => $unitRequest['name'],
                    'secondary_status' => $unitRequest['secondary_status'],
                    'amount' => $unitRequest['amount'],
                    'kingdom_id' => $kingdom->id,
                    'unit_id' => $gameUnit->id,
                    'queue_id' => $queue->id,
                    'is_cancel_request' => false,
                ];

                $data[] = $queueData;
            }
        }

        return array_values(collect(array_merge($this->fetchUnitCancellationQueueData($character), $data))->sortByDesc('time_left_seconds')->sortByDesc('is_cancel_request')->toArray());
    }

    /**
     * Fetch unit cancellation queue data.
     *
     * @param Character $character
     * @return array
     */
    private function fetchUnitCancellationQueueData(Character $character): array {
        $queues = CapitalCityUnitCancellation::where('character_id', $character->id)->whereNotNull('travel_time_completed_at')->get();

        $data = [];

        foreach ($queues as $queue) {
            $unit = GameUnit::where('id', $queue->unit_id)->first();

            $end = Carbon::parse($queue->travel_time_completed_at)->timestamp;
            $current = Carbon::now()->timestamp;

            $timeLeftInSeconds = 0;

            if (!now()->gt($queue->completed_at)) {
                $timeLeftInSeconds = $end - $current;
            }

            $data[] = [
                'kingdom_name' => $queue->kingdom->name . '(X/Y: '.$queue->kingdom->x_position.'/'.$queue->kingdom->y_position.')',
                'status' => $queue->status,
                'unit_name' => $unit->name,
                'secondary_status' => $queue->status === CapitalCityQueueStatus::CANCELLATION_REJECTED ? 'Cancellation was rejected. Building is either close to or has already finished.' : 'Cancellation request',
                'kingdom_id' => $queue->kingdom_id,
                'unit_id' => $unit->id,
                'queue_id' => $queue->id,
                'time_left_seconds' => max($timeLeftInSeconds, 0),
                'is_cancel_request' => true,
            ];
        }

        return $data;
    }

    /**
     * Fetch building cancellation queue data.
     *
     * @param Character $character
     * @return array
     */
    private function fetchBuildingCancellationQueueData(Character $character): array {
        $queues = CapitalCityBuildingCancellation::where('character_id', $character->id)->whereNotNull('travel_time_completed_at')->get();

        $data = [];

        foreach ($queues as $queue) {
            $building = KingdomBuilding::where('kingdom_id', $queue->kingdom_id)->where('id', $queue->building_id)->first();

            $end = Carbon::parse($queue->travel_time_completed_at)->timestamp;
            $current = Carbon::now()->timestamp;

            $timeLeftInSeconds = 0;

            if (!now()->gt($queue->completed_at)) {
                $timeLeftInSeconds = $end - $current;
            }

            $data[] = [
                'kingdom_name' => $queue->kingdom->name . '(X/Y: '.$queue->kingdom->x_position.'/'.$queue->kingdom->y_position.')',
                'status' => $queue->status,
                'building_name' => $building->name,
                'secondary_status' => $queue->status === CapitalCityQueueStatus::CANCELLATION_REJECTED ? 'Cancellation was rejected. Building is either close to or has already finished.' : 'Cancellation request',
                'kingdom_id' => $queue->kingdom_id,
                'building_id' => $building->id,
                'queue_id' => $queue->id,
                'time_left_seconds' => max($timeLeftInSeconds, 0),
                'is_cancel_request' => true,
            ];
        }

        return $data;
    }

    /**
     * Ensure only one capital city exists per game plane.
     *
     * @param Kingdom $kingdom
     * @return void
     */
    private function validateOneCapitalCityPerPlane(Kingdom $kingdom): void
    {
        $otherCapitalCitiesCount = Kingdom::where('game_map_id', $kingdom->game_map_id)
            ->where('is_capital', true)
            ->count();

        if ($otherCapitalCitiesCount > 0) {
            $this->errorResult('Cannot have more than one Capital city on plane: ' . $kingdom->gameMap->name);
        }
    }

    /**
     * Update the kingdom to mark it as a capital city.
     *
     * @param Kingdom $kingdom
     * @return void
     */
    private function updateKingdom(Kingdom $kingdom): void
    {

        $kingdom = $kingdom->refresh();

        $this->updateKingdom->updateKingdom($kingdom);
        $this->updateKingdom->updateKingdomAllKingdoms($kingdom->character);
    }

    /**
     * Retrieve other kingdoms owned by the character.
     *
     * @param Character $character
     * @param Kingdom $kingdom
     * @return EloquentCollection
     */
    private function getOtherKingdoms(Character $character, Kingdom $kingdom): EloquentCollection{
        return $character->kingdoms()->where('id', '!=', $kingdom->id)->where('game_map_id', $kingdom->game_map_id)->get();
    }

    /**
     * Fetch buildings data from other kingdoms for upgrades or repairs.
     *
     * @param EloquentCollection $kingdoms
     * @return array
     */
    private function fetchBuildingsData($kingdoms): array
    {
        $kingdomBuildingData = [];

        foreach ($kingdoms as $otherKingdom) {
            $buildings = $this->fetchBuildings($otherKingdom);
            $kingdomBuildingData[] = $this->formatKingdomBuildingData($otherKingdom, $buildings);
        }

        return $kingdomBuildingData;
    }

    /**
     * Fetch buildings from a specific kingdom for upgrades or repairs.
     *
     * @param Kingdom $kingdom
     * @return SupportCollection
     */
    private function fetchBuildings(Kingdom $kingdom): SupportCollection
    {
        $buildings = $kingdom->buildings()
            ->join('game_buildings', 'game_buildings.id', '=', 'kingdom_buildings.game_building_id')
            ->where('kingdom_buildings.is_locked', false)
            ->whereNotIn('kingdom_buildings.id', function ($query) use ($kingdom) {
                $query->select('building_id')
                    ->from('buildings_in_queue')
                    ->where('kingdom_id', $kingdom->id);
            })
            ->whereColumn('game_buildings.max_level', '>', 'kingdom_buildings.level')
            ->select('kingdom_buildings.*')
            ->get();

        return $this->filterOutCapitalCityBuildingsInQueue($buildings);

    }

    /**
     * Filters out buildings who are currently in the Capital City Building Queue.
     *
     * @param EloquentCollection $kingdomBuildings
     * @return SupportCollection
     */
    private function filterOutCapitalCityBuildingsInQueue(EloquentCollection $kingdomBuildings): SupportCollection
    {
        $buildingIds = $kingdomBuildings->pluck('id')->toArray();

        $capitalCityBuildingQueues = CapitalCityBuildingQueue::whereIn('kingdom_id', $kingdomBuildings->pluck('kingdom_id'))
            ->get();

        $invalidBuildingIds = $capitalCityBuildingQueues->flatMap(function ($queue) use ($buildingIds) {
            return collect($queue->building_request_data)->pluck('building_id')->intersect($buildingIds);
        })->unique()->toArray();


        return $kingdomBuildings->reject(function ($building) use ($invalidBuildingIds) {
            return in_array($building->id, $invalidBuildingIds);
        });
    }

    /**
     * Format kingdom and buildings data.
     *
     * @param Kingdom $kingdom
     * @param SupportCollection $buildings
     * @return array
     */
    private function formatKingdomBuildingData(Kingdom $kingdom, SupportCollection $buildings): array
    {
        $buildings = new Collection($buildings, $this->kingdomBuildingTransformer);
        $buildings = $this->manager->createData($buildings)->toArray();

        return [
            'kingdom_id' => $kingdom->id,
            'kingdom_name' => $kingdom->name,
            'x_position' => $kingdom->x_position,
            'y_position' => $kingdom->y_position,
            'map_name' => $kingdom->gameMap->name,
            'buildings' => $buildings,
        ];
    }

    /**
     * Retrieve selectable kingdoms for the given kingdom.
     *
     * @param Kingdom $kingdom
     * @return array
     */
    private function getSelectableKingdoms(Kingdom $kingdom): array
    {
        $kingdoms = Kingdom::where('id', '!=', $kingdom->id)
            ->where('character_id', $kingdom->character_id)
            ->where('game_map_id', $kingdom->game_map_id)
            ->whereDoesntHave('unitsQueue')
            ->select('name', 'id')
            ->get();

        return $this->filterOutCapitalCityUnitsInQueue($kingdoms)->toArray();
    }

    /**
     * Filters out buildings who are currently in the Capital City Building Queue.
     *
     * @param EloquentCollection $kingdomUnits
     * @return SupportCollection
     */
    private function filterOutCapitalCityUnitsInQueue(EloquentCollection $kingdomUnits): SupportCollection
    {
        $unitsIds = $kingdomUnits->pluck('id')->toArray();

        $capitalCityUnitQueue = CapitalCityUnitQueue::whereIn('kingdom_id', $kingdomUnits->pluck('id'))
            ->get();

        $invalidUnitIds = $capitalCityUnitQueue->flatMap(function ($queue) use ($unitsIds) {
            return collect($queue->building_request_data)->pluck('building_id')->intersect($unitsIds);
        })->unique()->toArray();

        return $kingdomUnits->reject(function ($unit) use ($invalidUnitIds) {
            return in_array($unit->id, $invalidUnitIds);
        });
    }

    /**
     * Update all kingdoms owned by the character as walked.
     *
     * @param Character $character
     * @param Kingdom $kingdom
     * @return void
     */
    private function updateWalkedKingdoms(Character $character, Kingdom $kingdom): void
    {
        $character->kingdoms()->where('game_map_id', $kingdom->game_map_id)->update([
            'last_walked' => now(),
            'auto_walked' => true,
        ]);
    }

    /**
     * Generate success message for making the kingdom a capital city.
     *
     * @param Kingdom $kingdom
     * @return string
     */
    private function getCapitalCityMessage(Kingdom $kingdom): string
    {
        return 'Your kingdom: ' . $kingdom->name . ' on plane: ' . $kingdom->gameMap->name . ' is now a capital city. ' .
            'You can manage all your cities on this plane from this kingdom. This kingdom will also appear at the top ' .
            'of your kingdom list with a special icon.';
    }
}
