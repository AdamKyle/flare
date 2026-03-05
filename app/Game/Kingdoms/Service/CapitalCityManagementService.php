<?php

namespace App\Game\Kingdoms\Service;

use DB;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection as SupportCollection;
use Carbon\Carbon;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;
use App\Flare\Models\CapitalCityBuildingQueue;
use App\Flare\Models\CapitalCityUnitCancellation;
use App\Flare\Models\CapitalCityUnitQueue;
use App\Flare\Models\Character;
use App\Flare\Models\CharacterPassiveSkill;
use App\Flare\Models\GameBuildingUnit;
use App\Flare\Models\GameUnit;
use App\Flare\Models\Kingdom;
use App\Flare\Models\KingdomBuilding;
use App\Flare\Transformers\CapitalCityKingdomBuildingTransformer;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use App\Game\PassiveSkills\Values\PassiveSkillTypeValue;

class CapitalCityManagementService
{
    use ResponseBuilder;

    public function __construct(
        private readonly UpdateKingdom $updateKingdom,
        private readonly CapitalCityBuildingManagement $capitalCityBuildingManagement,
        private readonly CapitalCityUnitManagement $capitalCityUnitManagement,
        private readonly CapitalCityKingdomBuildingTransformer $capitalCityKingdomBuildingTransformer,
        private readonly UnitMovementService $unitMovementService,
        private readonly Manager $manager
    ) {}

    public function makeCapitalCity(Kingdom $kingdom): array
    {
        $this->validateOneCapitalCityPerPlane($kingdom);

        $kingdom->update(['is_capital' => true]);
        $this->updateKingdom($kingdom);

        return $this->successResult([
            'message' => $this->getCapitalCityMessage($kingdom),
        ]);
    }

    public function fetchBuildingsForUpgradesOrRepairs(Character $character, Kingdom $kingdom, bool $returnArray = false): array
    {
        $kingdoms = $this->getOtherKingdoms($character, $kingdom);
        $kingdomBuildingData = $this->fetchBuildingsData($kingdom, $kingdoms);

        return $returnArray ? $kingdomBuildingData : $this->successResult($kingdomBuildingData);
    }

    public function fetchKingdomsForSelection(Kingdom $kingdom, bool $returnArray = false): array
    {
        $kingdoms = $this->getSelectableKingdoms($kingdom);

        if ($returnArray) {
            return $kingdoms;
        }

        return $this->successResult(['kingdoms' => $kingdoms]);
    }

    public function walkAllKingdoms(Character $character, Kingdom $kingdom): array
    {
        $this->updateWalkedKingdoms($character, $kingdom);
        $this->updateKingdom($kingdom);

        return $this->successResult(['message' => 'All kingdoms walked!']);
    }

    public function sendoffBuildingRequests(Character $character, Kingdom $kingdom, array $params, string $type): array
    {
        return $this->capitalCityBuildingManagement->createBuildingUpgradeRequestQueue($character, $kingdom, $params, $type);
    }

    public function sendOffUnitRecruitmentOrders(Character $character, Kingdom $kingdom, array $requestData): array
    {
        return $this->capitalCityUnitManagement->createUnitRequests($character, $kingdom, $requestData);
    }

    public function fetchBuildingQueueData(Character $character, ?Kingdom $kingdom = null): array
    {
        $currentTime = now();

        $queues = CapitalCityBuildingQueue::query()
            ->where('character_id', $character->id)
            ->when($kingdom, function ($query) use ($kingdom) {
                return $query->whereHas('kingdom', function ($query) use ($kingdom) {
                    $query->where('game_map_id', $kingdom->game_map_id);
                })->where('kingdom_id', '!=', $kingdom->id);
            })
            ->with(['kingdom.gameMap:id,name'])
            ->select(['id', 'kingdom_id', 'character_id', 'status', 'building_request_data', 'completed_at'])
            ->get();

        $missingBuildingIdsByKingdomId = [];

        foreach ($queues as $queue) {
            $queueKingdomId = $queue->kingdom_id;

            foreach ($queue->building_request_data as $request) {
                if (! array_key_exists('building_name', $request) || empty($request['building_name'])) {
                    $missingBuildingIdsByKingdomId[$queueKingdomId][] = (int) $request['building_id'];
                }
            }
        }

        $buildingNamesByKingdomId = [];

        foreach ($missingBuildingIdsByKingdomId as $queueKingdomId => $buildingIds) {
            $uniqueBuildingIds = array_values(array_unique($buildingIds));

            if (empty($uniqueBuildingIds)) {
                continue;
            }

            $buildings = KingdomBuilding::query()
                ->where('kingdom_id', $queueKingdomId)
                ->whereIn('id', $uniqueBuildingIds)
                ->select(['id', 'kingdom_id', 'name'])
                ->get();

            foreach ($buildings as $building) {
                $buildingNamesByKingdomId[$queueKingdomId][$building->id] = $building->name;
            }
        }

        $buildingQueueData = $queues->map(function ($queue) use ($currentTime, $buildingNamesByKingdomId) {
            $kingdom = $queue->kingdom;

            $queueTimeLeftInSeconds = 0;

            if (! $queue->completed_at->lte($currentTime)) {
                $queueTimeLeftInSeconds = $currentTime->diffInSeconds($queue->completed_at);
            }

            $buildingRequests = collect($queue->building_request_data)->map(function ($request) use ($kingdom, $buildingNamesByKingdomId) {
                $buildingId = (int) $request['building_id'];

                $buildingName = $request['building_name'] ?? null;

                if (empty($buildingName)) {
                    $buildingName = $buildingNamesByKingdomId[$kingdom->id][$buildingId] ?? '';
                }

                return [
                    'building_name' => $buildingName,
                    'secondary_status' => $request['secondary_status'],
                    'building_id' => $buildingId,
                    'from_level' => $request['from_level'],
                    'to_level' => $request['to_level'],
                ];
            })->toArray();

            $buildingRequests = $this->reorderBuildingRequests($buildingRequests);

            return [
                'kingdom_id' => $kingdom->id,
                'kingdom_name' => $kingdom->name,
                'map_name' => $kingdom->gameMap->name,
                'status' => $queue->status,
                'building_queue' => $buildingRequests,
                'total_time' => $queueTimeLeftInSeconds,
                'queue_id' => $queue->id,
            ];
        });

        return array_values($buildingQueueData->toArray());
    }

    public function fetchUnitQueueData(Character $character, ?Kingdom $kingdom = null): array
    {

        $queues = CapitalCityUnitQueue::where('character_id', $character->id)
            ->when($kingdom, function ($query) use ($kingdom) {
                return $query->whereHas('kingdom', function ($query) use ($kingdom) {
                    $query->where('game_map_id', $kingdom->game_map_id);
                })->where('kingdom_id', '!=', $kingdom->id);
            })
            ->get();

        $unitQueueData = $queues->map(function ($queue) {
            $kingdom = $queue->kingdom;
            $queueTimeLeftInSeconds = now()->diffInSeconds($queue->completed_at);

            if ($queue->completed_at->lte(now())) {
                $queueTimeLeftInSeconds = 0;
            }

            $unitRequests = collect($queue->unit_request_data)->map(function ($request) {

                return [
                    'unit_name' => $request['name'],
                    'secondary_status' => $request['secondary_status'],
                    'amount_to_recruit' => $request['amount'],
                ];
            })->toArray();

            $unitRequests = $this->reorderUnitRequests($unitRequests);

            return [
                'queue_id' => $queue->id,
                'kingdom_id' => $kingdom->id,
                'kingdom_name' => $kingdom->name,
                'map_name' => $kingdom->gameMap->name,
                'unit_requests' => $unitRequests,
                'status' => $queue->status,
                'total_time' => $queueTimeLeftInSeconds,
            ];
        });

        return array_values($unitQueueData->toArray());
    }


    /**
     * Reorder the unit requests
     *
     * @param array $requestData
     * @return array
     */
    private function reorderBuildingRequests(array $requestData): array
    {
        $statusOrder = [
            CapitalCityQueueStatus::FINISHED => 1,
            CapitalCityQueueStatus::TRAVELING => 2,
            CapitalCityQueueStatus::REQUESTING => 3,
            CapitalCityQueueStatus::BUILDING => 4,
            CapitalCityQueueStatus::CANCELLED => 5,
            CapitalCityQueueStatus::REJECTED => 6,
        ];

        usort($requestData, function ($a, $b) use ($statusOrder) {
            return $statusOrder[$a['secondary_status']] <=> $statusOrder[$b['secondary_status']];
        });

        return $requestData;
    }

    /**
     * Reorder the unit requests
     *
     * @param array $requestData
     * @return array
     */
    private function reorderUnitRequests(array $requestData): array
    {
        $statusOrder = [
            CapitalCityQueueStatus::FINISHED => 1,
            CapitalCityQueueStatus::TRAVELING => 2,
            CapitalCityQueueStatus::REQUESTING => 3,
            CapitalCityQueueStatus::RECRUITING => 4,
            CapitalCityQueueStatus::CANCELLED => 5,
            CapitalCityQueueStatus::REJECTED => 6,
        ];

        usort($requestData, function ($a, $b) use ($statusOrder) {
            return $statusOrder[$a['secondary_status']] <=> $statusOrder[$b['secondary_status']];
        });

        return $requestData;
    }

    /**
     * Fetch unit cancellation queue data.
     */
    private function fetchUnitCancellationQueueData(Character $character): array
    {
        $queues = CapitalCityUnitCancellation::where('character_id', $character->id)->whereNotNull('travel_time_completed_at')->get();

        $data = [];

        foreach ($queues as $queue) {
            $unit = GameUnit::where('id', $queue->unit_id)->first();

            $end = Carbon::parse($queue->travel_time_completed_at)->timestamp;
            $current = Carbon::now()->timestamp;

            $timeLeftInSeconds = 0;

            if (! now()->gt($queue->completed_at)) {
                $timeLeftInSeconds = $end - $current;
            }

            $data[] = [
                'kingdom_name' => $queue->kingdom->name . '(X/Y: ' . $queue->kingdom->x_position . '/' . $queue->kingdom->y_position . ')',
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
     * Ensure only one capital city exists per game plane.
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
     */
    private function updateKingdom(Kingdom $kingdom): void
    {

        $kingdom = $kingdom->refresh();

        $this->updateKingdom->updateKingdom($kingdom);
        $this->updateKingdom->updateKingdomAllKingdoms($kingdom->character);
    }

    /**
     * Retrieve valid kingdoms.
     *
     * - Where isnt the capital city
     * - Where does have capital city queue
     *
     * @param Character $character
     * @param Kingdom $kingdom
     * @return EloquentCollection
     */
    private function getOtherKingdoms(Character $character, Kingdom $kingdom): EloquentCollection
    {
        return $character->kingdoms()
            ->whereDoesntHave('capitalCityBuildingQueue')
            ->where('id', '!=', $kingdom->id)
            ->where('game_map_id', $kingdom->game_map_id)
            ->with('gameMap:id,name')
            ->get();
    }

    /**
     * Fetch buildings data from other kingdoms for upgrades or repairs.
     *
     * @param  EloquentCollection  $kingdoms
     */
    private function fetchBuildingsData(Kingdom $kingdom, SupportCollection $kingdoms): array
    {
        $kingdomBuildingData = [];

        if ($kingdoms->isEmpty()) {
            return $kingdomBuildingData;
        }

        $kingdomIds = $kingdoms->pluck('id')->values()->toArray();

        $buildings = KingdomBuilding::query()
            ->whereIn('kingdom_buildings.kingdom_id', $kingdomIds)
            ->join('game_buildings', 'game_buildings.id', '=', 'kingdom_buildings.game_building_id')
            ->where('kingdom_buildings.is_locked', false)
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('buildings_in_queue')
                    ->whereColumn('buildings_in_queue.kingdom_id', 'kingdom_buildings.kingdom_id')
                    ->whereColumn('buildings_in_queue.building_id', 'kingdom_buildings.id');
            })
            ->whereColumn('game_buildings.max_level', '>', 'kingdom_buildings.level')
            ->select('kingdom_buildings.*')
            ->with(['gameBuilding.passive'])
            ->get();

        $buildings = $this->filterOutCapitalCityBuildingsInQueue($buildings);

        $this->primeCapitalCityKingdomBuildingTransformer($kingdom->character_id, $buildings);

        $buildingsByKingdomId = $buildings->groupBy('kingdom_id');

        foreach ($kingdoms as $otherKingdom) {
            $buildingsForKingdom = $buildingsByKingdomId->get($otherKingdom->id, collect());
            $kingdomBuildingData[] = $this->formatKingdomBuildingData($kingdom, $otherKingdom, $buildingsForKingdom);
        }

        return $kingdomBuildingData;
    }

    /**
     * Fetch valid buildings.
     *
     * Fetch buildings who are:
     *
     * - Not locked
     * - Not currently in queue from manual upgrade
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
     */
    private function filterOutCapitalCityBuildingsInQueue(EloquentCollection $kingdomBuildings): SupportCollection
    {
        if ($kingdomBuildings->isEmpty()) {
            return $kingdomBuildings;
        }

        $buildingIds = $kingdomBuildings->pluck('id')->toArray();
        $kingdomIds = $kingdomBuildings->pluck('kingdom_id')->unique()->values()->toArray();

        $capitalCityBuildingQueues = CapitalCityBuildingQueue::query()
            ->whereIn('kingdom_id', $kingdomIds)
            ->select(['id', 'kingdom_id', 'building_request_data'])
            ->get();

        if ($capitalCityBuildingQueues->isEmpty()) {
            return $kingdomBuildings;
        }

        $invalidBuildingIds = $capitalCityBuildingQueues->flatMap(function ($queue) use ($buildingIds) {
            return collect($queue->building_request_data)->pluck('building_id')->intersect($buildingIds);
        })->unique()->toArray();

        if (empty($invalidBuildingIds)) {
            return $kingdomBuildings;
        }

        return $kingdomBuildings->reject(function ($building) use ($invalidBuildingIds) {
            return in_array($building->id, $invalidBuildingIds, true);
        });
    }

    /**
     * Format kingdom and buildings data.
     */
    private function formatKingdomBuildingData(Kingdom $capitalCityKingdom, Kingdom $kingdomForRequest, SupportCollection $buildings): array
    {
        $buildings = new Collection($buildings, $this->capitalCityKingdomBuildingTransformer);
        $buildings = $this->manager->createData($buildings)->toArray();

        $character = $capitalCityKingdom->character;

        return [
            'kingdom_id' => $kingdomForRequest->id,
            'kingdom_name' => $kingdomForRequest->name,
            'x_position' => $kingdomForRequest->x_position,
            'y_position' => $kingdomForRequest->y_position,
            'map_name' => $kingdomForRequest->gameMap->name,
            'buildings' => $buildings,
            'total_travel_time' => $this->unitMovementService->getDistanceTime($character, $kingdomForRequest, $capitalCityKingdom, PassiveSkillTypeValue::CAPITAL_CITY_REQUEST_BUILD_TRAVEL_TIME_REDUCTION),

        ];
    }

    /**
     * Retrieve selectable kingdoms for the given kingdom.
     */
    private function getSelectableKingdoms(Kingdom $kingdom): array
    {
        $character = $kingdom->character;

        $kingdoms = Kingdom::where('id', '!=', $kingdom->id)
            ->where('character_id', $kingdom->character_id)
            ->where('game_map_id', $kingdom->game_map_id)
            ->whereDoesntHave('unitsQueue')
            ->with('gameMap:id,name')
            ->select('name', 'id', 'game_map_id', 'x_position', 'y_position')
            ->get()
            ->each(function ($selectableKingdom) use ($character, $kingdom) {
                $selectableKingdom->game_map_name = $kingdom->gameMap->name;
                $selectableKingdom->time_to_kingdom = $this->unitMovementService->getDistanceTime($character, $selectableKingdom, $kingdom, PassiveSkillTypeValue::CAPITAL_CITY_REQUEST_UNIT_TRAVEL_TIME_REDUCTION);
                $selectableKingdom->makeHidden(['gameMap', 'x_position', 'y_position']);
            });

        return $this->filterOutCapitalCityUnitsInQueue($kingdoms)->toArray();
    }

    /**
     * Filters out buildings who are currently in the Capital City Building Queue.
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
            return in_array($unit->id, $invalidUnitIds, true);
        });
    }

    /**
     * Update all kingdoms owned by the character as walked.
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
     */
    private function getCapitalCityMessage(Kingdom $kingdom): string
    {
        return 'Your kingdom: ' . $kingdom->name . ' on plane: ' . $kingdom->gameMap->name . ' is now a capital city. ' .
            'You can manage all your cities on this plane from this kingdom. This kingdom will also appear at the top ' .
            'of your kingdom list with a special icon.';
    }

    private function primeCapitalCityKingdomBuildingTransformer(int $characterId, EloquentCollection $buildings): void
    {
        $gameBuildingIds = $buildings->pluck('game_building_id')->unique()->values()->toArray();

        if (empty($gameBuildingIds)) {
            $this->capitalCityKingdomBuildingTransformer->primeCaches([], [], $characterId);

            return;
        }

        $unitsForBuildings = GameBuildingUnit::query()
            ->whereIn('game_building_id', $gameBuildingIds)
            ->with('gameUnit:id,name')
            ->get(['game_building_id', 'game_unit_id', 'required_level']);

        $unitsForBuildingByGameBuildingId = [];

        foreach ($unitsForBuildings as $unitForBuilding) {
            $unitsForBuildingByGameBuildingId[$unitForBuilding->game_building_id][] = [
                'unit_name' => $unitForBuilding->gameUnit->name,
                'at_building_level' => $unitForBuilding->required_level,
            ];
        }

        $passiveSkillIds = $buildings->map(function (KingdomBuilding $building) {
            return $building->gameBuilding?->passive?->id;
        })->filter()->unique()->values()->toArray();

        $characterPassiveSkillsBySkillId = [];

        if (! empty($passiveSkillIds)) {
            $characterPassiveSkills = CharacterPassiveSkill::query()
                ->where('character_id', $characterId)
                ->whereIn('passive_skill_id', $passiveSkillIds)
                ->get(['passive_skill_id', 'current_level']);

            foreach ($characterPassiveSkills as $characterPassiveSkill) {
                $characterPassiveSkillsBySkillId[$characterPassiveSkill->passive_skill_id] = $characterPassiveSkill->current_level;
            }
        }

        $this->capitalCityKingdomBuildingTransformer->primeCaches(
            $unitsForBuildingByGameBuildingId,
            $characterPassiveSkillsBySkillId,
            $characterId
        );
    }
}