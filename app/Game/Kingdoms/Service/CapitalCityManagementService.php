<?php

namespace App\Game\Kingdoms\Service;

use App\Flare\Models\CapitalCityBuildingQueue;
use App\Flare\Models\CapitalCityUnitCancellation;
use App\Flare\Models\CapitalCityUnitQueue;
use App\Flare\Models\Character;
use App\Flare\Models\CharacterPassiveSkill;
use App\Flare\Models\GameBuildingUnit;
use App\Flare\Models\GameUnit;
use App\Flare\Models\Kingdom;
use App\Flare\Models\KingdomBuilding;
use App\Flare\Models\UnitInQueue;
use App\Flare\Transformers\CapitalCityKingdomBuildingTransformer;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use App\Game\PassiveSkills\Values\PassiveSkillTypeValue;
use Carbon\Carbon;
use DB;
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
            ->whereNotIn('status', $this->capitalCityTerminalStatuses())
            ->when($kingdom, function ($query) use ($kingdom) {
                return $query->whereHas('kingdom', function ($query) use ($kingdom) {
                    $query->where('game_map_id', $kingdom->game_map_id);
                })->where('kingdom_id', '!=', $kingdom->id);
            })
            ->with(['kingdom.gameMap:id,name'])
            ->select(['id', 'kingdom_id', 'character_id', 'status', 'building_request_data', 'started_at', 'completed_at'])
            ->get()
            ->filter(function (CapitalCityBuildingQueue $queue) use ($currentTime) {
                return $this->capitalCityQueueShouldBeVisible($queue->status, $queue->completed_at, $currentTime);
            });

        $missingBuildingIdsByKingdomId = [];

        foreach ($queues as $queue) {
            $queueKingdomId = $queue->kingdom_id;

            foreach ($this->filterActiveCapitalCityQueueRequests($queue->building_request_data) as $request) {
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
            $activeBuildingRequestData = $this->filterActiveCapitalCityQueueRequests($queue->building_request_data);

            if (empty($activeBuildingRequestData)) {
                return null;
            }

            $queueTimeLeftInSeconds = 0;

            if ($this->capitalCityBuildingHasActiveTimer($queue->status) && ! $queue->completed_at->lte($currentTime)) {
                $queueTimeLeftInSeconds = $currentTime->diffInSeconds($queue->completed_at);
            }

            $buildingRequests = collect($activeBuildingRequestData)->map(function ($request) use ($kingdom, $buildingNamesByKingdomId) {
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
                'time_remaining' => $queueTimeLeftInSeconds,
                'timer_duration' => $this->getQueueDurationInSeconds($queue->started_at, $queue->completed_at),
                'timer_started_at' => $queue->started_at->timestamp * 1000,
                'started_at' => $queue->started_at->toIso8601String(),
                'completed_at' => $queue->completed_at->toIso8601String(),
                'completed_at_timestamp' => $queue->completed_at->timestamp * 1000,
                'phase_timer_label' => $this->capitalCityBuildingPhaseTimerLabel($queue->status),
                'queue_id' => $queue->id,
            ];
        });

        return array_values($buildingQueueData->filter()->toArray());
    }

    public function fetchUnitQueueData(Character $character, ?Kingdom $kingdom = null): array
    {
        $currentTime = now();

        $queues = CapitalCityUnitQueue::query()
            ->where('character_id', $character->id)
            ->whereNotIn('status', $this->capitalCityTerminalStatuses())
            ->when($kingdom, function ($query) use ($kingdom) {
                return $query->whereHas('kingdom', function ($query) use ($kingdom) {
                    $query->where('game_map_id', $kingdom->game_map_id);
                })->where('kingdom_id', '!=', $kingdom->id);
            })
            ->with(['kingdom.gameMap:id,name'])
            ->select(['id', 'kingdom_id', 'character_id', 'status', 'unit_request_data', 'started_at', 'completed_at'])
            ->get()
            ->filter(function (CapitalCityUnitQueue $queue) use ($currentTime) {
                return $this->capitalCityQueueShouldBeVisible($queue->status, $queue->completed_at, $currentTime);
            });

        $unitQueueData = $queues->map(function ($queue) use ($currentTime) {
            $kingdom = $queue->kingdom;
            $activeUnitRequestData = $this->filterActiveCapitalCityQueueRequests($queue->unit_request_data);

            if (empty($activeUnitRequestData)) {
                return null;
            }

            $queueTimeLeftInSeconds = 0;

            if ($this->capitalCityUnitHasActiveTimer($queue->status) && $queue->completed_at->greaterThan($currentTime)) {
                $queueTimeLeftInSeconds = $currentTime->diffInSeconds($queue->completed_at);
            }

            $unitRequests = collect($activeUnitRequestData)->map(function ($request) use ($queue) {

                return [
                    'queue_id' => $queue->id,
                    'unit_name' => $request['name'],
                    'secondary_status' => $request['secondary_status'],
                    'amount_to_recruit' => $request['amount'],
                ];
            })->toArray();

            $unitRequests = $this->reorderUnitRequests($unitRequests);

            return [
                'queue_id' => $queue->id,
                'queue_ids' => [$queue->id],
                'kingdom_id' => $kingdom->id,
                'kingdom_name' => $kingdom->name,
                'map_name' => $kingdom->gameMap->name,
                'unit_requests' => $unitRequests,
                'status' => $queue->status,
                'total_time' => $queueTimeLeftInSeconds,
                'time_remaining' => $queueTimeLeftInSeconds,
                'timer_duration' => $this->getQueueDurationInSeconds($queue->started_at, $queue->completed_at),
                'timer_started_at' => $queue->started_at->timestamp * 1000,
                'started_at' => $queue->started_at->toIso8601String(),
                'completed_at' => $queue->completed_at->toIso8601String(),
                'completed_at_timestamp' => $queue->completed_at->timestamp * 1000,
                'phase_timer_label' => $this->capitalCityUnitPhaseTimerLabel($queue->status),
            ];
        });

        return array_values($unitQueueData->filter()->groupBy('kingdom_id')->map(function (SupportCollection $queueGroups) {
            $firstQueueGroup = $queueGroups->first();
            $unitRequests = $queueGroups
                ->flatMap(fn (array $queueGroup) => $queueGroup['unit_requests'])
                ->values()
                ->toArray();

            $queueIds = $queueGroups
                ->flatMap(fn (array $queueGroup) => $queueGroup['queue_ids'])
                ->unique()
                ->values()
                ->toArray();

            $activeQueueGroup = $queueGroups
                ->sortByDesc('total_time')
                ->first();

            return [
                'queue_id' => $firstQueueGroup['queue_id'],
                'queue_ids' => $queueIds,
                'kingdom_id' => $firstQueueGroup['kingdom_id'],
                'kingdom_name' => $firstQueueGroup['kingdom_name'],
                'map_name' => $firstQueueGroup['map_name'],
                'unit_requests' => $this->reorderUnitRequests($unitRequests),
                'status' => $activeQueueGroup['status'],
                'total_time' => $activeQueueGroup['total_time'],
                'time_remaining' => $activeQueueGroup['time_remaining'],
                'timer_duration' => $activeQueueGroup['timer_duration'],
                'timer_started_at' => $activeQueueGroup['timer_started_at'],
                'started_at' => $activeQueueGroup['started_at'],
                'completed_at' => $activeQueueGroup['completed_at'],
                'completed_at_timestamp' => $activeQueueGroup['completed_at_timestamp'],
                'phase_timer_label' => $activeQueueGroup['phase_timer_label'],
            ];
        })->toArray());
    }

    /**
     * Reorder the unit requests
     */
    private function reorderBuildingRequests(array $requestData): array
    {
        $statusOrder = [
            CapitalCityQueueStatus::FINISHED => 1,
            CapitalCityQueueStatus::TRAVELING => 2,
            CapitalCityQueueStatus::REQUESTING => 3,
            CapitalCityQueueStatus::BUILDING => 4,
            CapitalCityQueueStatus::REPAIRING => 5,
            CapitalCityQueueStatus::CANCELLED => 6,
            CapitalCityQueueStatus::REJECTED => 7,
            CapitalCityQueueStatus::CANCELLATION_REJECTED => 8,
        ];

        usort($requestData, function ($a, $b) use ($statusOrder) {
            return $statusOrder[$a['secondary_status']] <=> $statusOrder[$b['secondary_status']];
        });

        return $requestData;
    }

    /**
     * Reorder the unit requests
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
            CapitalCityQueueStatus::CANCELLATION_REJECTED => 7,
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
                'kingdom_name' => $queue->kingdom->name.'(X/Y: '.$queue->kingdom->x_position.'/'.$queue->kingdom->y_position.')',
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
            $this->errorResult('Cannot have more than one Capital city on plane: '.$kingdom->gameMap->name);
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
     */
    private function getOtherKingdoms(Character $character, Kingdom $kingdom): EloquentCollection
    {
        return $character->kingdoms()
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
                    ->whereColumn('buildings_in_queue.building_id', 'kingdom_buildings.id')
                    ->where('buildings_in_queue.completed_at', '>', now());
            })
            ->where(function ($query) {
                $query->whereColumn('game_buildings.max_level', '>', 'kingdom_buildings.level')
                    ->orWhereColumn('kingdom_buildings.current_durability', '<', 'kingdom_buildings.max_durability');
            })
            ->select('kingdom_buildings.*')
            ->with(['gameBuilding.passive'])
            ->get();

        $buildings = $this->filterOutCapitalCityBuildingsInQueue($buildings);

        $this->primeCapitalCityKingdomBuildingTransformer($kingdom->character_id, $buildings);

        $buildingsByKingdomId = $buildings->groupBy('kingdom_id');

        foreach ($kingdoms as $otherKingdom) {
            $buildingsForKingdom = $buildingsByKingdomId->get($otherKingdom->id, collect());

            if ($buildingsForKingdom->isEmpty()) {
                continue;
            }

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
     */
    private function fetchBuildings(Kingdom $kingdom): SupportCollection
    {
        $buildings = $kingdom->buildings()
            ->join('game_buildings', 'game_buildings.id', '=', 'kingdom_buildings.game_building_id')
            ->where('kingdom_buildings.is_locked', false)
            ->whereNotIn('kingdom_buildings.id', function ($query) use ($kingdom) {
                $query->select('building_id')
                    ->from('buildings_in_queue')
                    ->where('kingdom_id', $kingdom->id)
                    ->where('completed_at', '>', now());
            })
            ->where(function ($query) {
                $query->whereColumn('game_buildings.max_level', '>', 'kingdom_buildings.level')
                    ->orWhereColumn('kingdom_buildings.current_durability', '<', 'kingdom_buildings.max_durability');
            })
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
            ->whereNotIn('status', $this->capitalCityTerminalStatuses())
            ->select(['id', 'kingdom_id', 'building_request_data'])
            ->get();

        if ($capitalCityBuildingQueues->isEmpty()) {
            return $kingdomBuildings;
        }

        $invalidBuildingIds = $capitalCityBuildingQueues->flatMap(function ($queue) use ($buildingIds) {
            return collect($queue->building_request_data)
                ->reject(function (array $request) {
                    return $this->capitalCityQueueRequestIsTerminal($request);
                })
                ->pluck('building_id')
                ->intersect($buildingIds);
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
        $buildings = $this->manager->createData($buildings)->toArray()['data'] ?? [];

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
            ->with('gameMap:id,name')
            ->select('name', 'id', 'game_map_id', 'x_position', 'y_position')
            ->get();

        $kingdomIds = $kingdoms->pluck('id')->values()->all();
        $unitNames = GameUnit::query()
            ->orderBy('name')
            ->pluck('name')
            ->toArray();
        $manualQueuedUnitNamesByKingdom = $this->getManualQueuedUnitNamesByKingdom($kingdomIds);
        $capitalCityQueuedUnitNamesByKingdom = $this->getCapitalCityQueuedUnitNamesByKingdom($kingdomIds);

        $kingdoms->each(function ($selectableKingdom) use ($character, $kingdom, $unitNames, $manualQueuedUnitNamesByKingdom, $capitalCityQueuedUnitNamesByKingdom) {
            $selectableKingdom->game_map_name = $kingdom->gameMap->name;
            $selectableKingdom->time_to_kingdom = $this->unitMovementService->getDistanceTime($character, $selectableKingdom, $kingdom, PassiveSkillTypeValue::CAPITAL_CITY_REQUEST_UNIT_TRAVEL_TIME_REDUCTION);
            $unavailableUnitNames = array_unique(array_merge(
                $manualQueuedUnitNamesByKingdom[$selectableKingdom->id] ?? [],
                $capitalCityQueuedUnitNamesByKingdom[$selectableKingdom->id] ?? [],
            ));
            $selectableKingdom->available_unit_types = array_values(array_diff($unitNames, $unavailableUnitNames));
            $selectableKingdom->makeHidden(['gameMap', 'x_position', 'y_position']);
        });

        return $this->filterOutCapitalCityUnitsInQueue($kingdoms)->toArray();
    }

    /**
     * Filters out buildings who are currently in the Capital City Building Queue.
     */
    private function filterOutCapitalCityUnitsInQueue(EloquentCollection $kingdomUnits): SupportCollection
    {
        return $kingdomUnits->filter(function ($kingdom) {
            return ! empty($kingdom->available_unit_types);
        })->values();
    }

    private function getManualQueuedUnitNamesByKingdom(array $kingdomIds): array
    {
        if (empty($kingdomIds)) {
            return [];
        }

        $unitNamesByKingdom = [];

        UnitInQueue::query()
            ->join('game_units', 'game_units.id', '=', 'units_in_queue.game_unit_id')
            ->whereIn('units_in_queue.kingdom_id', $kingdomIds)
            ->where('units_in_queue.completed_at', '>', now())
            ->get(['units_in_queue.kingdom_id', 'game_units.name'])
            ->each(function ($row) use (&$unitNamesByKingdom) {
                $unitNamesByKingdom[$row->kingdom_id][] = $row->name;
            });

        return $unitNamesByKingdom;
    }

    private function getCapitalCityQueuedUnitNamesByKingdom(array $kingdomIds): array
    {
        if (empty($kingdomIds)) {
            return [];
        }

        $unitNamesByKingdom = [];

        CapitalCityUnitQueue::whereIn('kingdom_id', $kingdomIds)
            ->whereNotIn('status', $this->capitalCityTerminalStatuses())
            ->get(['kingdom_id', 'unit_request_data'])
            ->each(function (CapitalCityUnitQueue $queue) use (&$unitNamesByKingdom) {
                foreach ($queue->unit_request_data as $request) {
                    if ($this->capitalCityQueueRequestIsTerminal($request)) {
                        continue;
                    }

                    if (empty($request['name'])) {
                        continue;
                    }

                    $unitNamesByKingdom[$queue->kingdom_id][] = $request['name'];
                }
            });

        return $unitNamesByKingdom;
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
        return 'Your kingdom: '.$kingdom->name.' on plane: '.$kingdom->gameMap->name.' is now a capital city. '.
            'You can manage all your cities on this plane from this kingdom. This kingdom will also appear at the top '.
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

    private function capitalCityTerminalStatuses(): array
    {
        return [
            CapitalCityQueueStatus::REJECTED,
            CapitalCityQueueStatus::FINISHED,
            CapitalCityQueueStatus::CANCELLED,
            CapitalCityQueueStatus::CANCELLATION_REJECTED,
        ];
    }

    private function capitalCityQueueRequestIsTerminal(array $request): bool
    {
        return in_array($request['secondary_status'] ?? null, $this->capitalCityTerminalStatuses(), true);
    }

    private function filterActiveCapitalCityQueueRequests(array $requests): array
    {
        return collect($requests)
            ->reject(fn (array $request) => $this->capitalCityQueueRequestIsTerminal($request))
            ->values()
            ->toArray();
    }

    private function capitalCityQueueShouldBeVisible(string $status, Carbon $completedAt, Carbon $currentTime): bool
    {
        if (in_array($status, $this->capitalCityTerminalStatuses(), true)) {
            return false;
        }

        if (! $this->capitalCityBuildingHasActiveTimer($status) && ! $this->capitalCityUnitHasActiveTimer($status)) {
            return true;
        }

        return $completedAt->greaterThan($currentTime);
    }

    private function capitalCityBuildingPhaseTimerLabel(string $status): string
    {
        return match ($status) {
            CapitalCityQueueStatus::TRAVELING => 'Traveling',
            CapitalCityQueueStatus::REQUESTING => 'Requesting Resources',
            CapitalCityQueueStatus::BUILDING => 'Building',
            CapitalCityQueueStatus::REPAIRING => 'Repairing',
            CapitalCityQueueStatus::PROCESSING => 'Processing',
            default => 'Building',
        };
    }

    private function capitalCityUnitPhaseTimerLabel(string $status): string
    {
        return match ($status) {
            CapitalCityQueueStatus::TRAVELING => 'Traveling',
            CapitalCityQueueStatus::REQUESTING => 'Requesting Resources',
            CapitalCityQueueStatus::RECRUITING => 'Recruiting',
            CapitalCityQueueStatus::PROCESSING => 'Processing',
            default => 'Processing',
        };
    }

    private function capitalCityBuildingHasActiveTimer(string $status): bool
    {
        return in_array($status, [
            CapitalCityQueueStatus::TRAVELING,
            CapitalCityQueueStatus::REQUESTING,
            CapitalCityQueueStatus::BUILDING,
            CapitalCityQueueStatus::REPAIRING,
        ], true);
    }

    private function capitalCityUnitHasActiveTimer(string $status): bool
    {
        return in_array($status, [
            CapitalCityQueueStatus::TRAVELING,
            CapitalCityQueueStatus::REQUESTING,
            CapitalCityQueueStatus::RECRUITING,
        ], true);
    }

    private function getQueueDurationInSeconds(Carbon $startedAt, Carbon $completedAt): int
    {
        return (int) max(0, $startedAt->diffInSeconds($completedAt));
    }
}
