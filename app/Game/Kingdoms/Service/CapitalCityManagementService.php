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
use App\Flare\Models\GameUnit;
use App\Flare\Models\Kingdom;
use App\Flare\Models\KingdomBuilding;
use App\Flare\Models\UnitInQueue;
use App\Flare\Transformers\KingdomBuildingTransformer;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;

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
        $kingdomBuildingData = $this->fetchBuildingsData($kingdoms);

        return $returnArray ? $kingdomBuildingData : $this->successResult($kingdomBuildingData);
    }

    public function fetchKingdomsForSelection(Kingdom $kingdom): array
    {
        $kingdoms = $this->getSelectableKingdoms($kingdom);

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
        $queues = CapitalCityBuildingQueue::where('character_id', $character->id)
            ->when($kingdom, function ($query) use ($kingdom) {
                return $query->whereHas('kingdom', function ($query) use ($kingdom) {
                    $query->where('game_map_id', $kingdom->game_map_id);
                })->where('kingdom_id', '!=', $kingdom->id);
            })
            ->get();

        $buildingQueueData = $queues->map(function ($queue)  {
            $kingdom = $queue->kingdom;
            $queueTimeLeftInSeconds = now()->diffInSeconds($queue->completed_at);

            if ($queue->completed_at->lte(now())) {
                $queueTimeLeftInSeconds = 0;
            }

            $buildingRequests = collect($queue->building_request_data)->map(function ($request) use ($kingdom, $queue) {

                $building = KingdomBuilding::where('kingdom_id', $kingdom->id)
                    ->where('id', $request['building_id'])
                    ->first();

                return [
                    'building_name' => $building->name,
                    'secondary_status' => $request['secondary_status'],
                    'building_id' => $building->id,
                    'queue_id' => $queue->id,
                    'is_cancel_request' => false,
                ];
            });

            return [
                'kingdom_id' => $kingdom->id,
                'kingdom_name' => $kingdom->name,
                'map_name' => $kingdom->gameMap->name,
                'status' => $queue->status,
                'building_queue' => $buildingRequests->sortByDesc('time_left_seconds')->values()->all(),
                'total_time' => $queueTimeLeftInSeconds,
            ];
        });

        return array_values($buildingQueueData->toArray());
    }

    public function fetchUnitQueueData(Character $character, ?Kingdom $kingdom = null): array
    {

        $queues = CapitalCityUnitQueue::where('character_id', $character->id);

        if (! is_null($kingdom)) {
            $queues = $queues->where('kingdom_id', '!=', $kingdom->id);
        }

        $queues = $queues->get();

        $data = [];

        foreach ($queues as $queue) {
            $kingdom = $queue->kingdom;

            $end = Carbon::parse($queue->completed_at)->timestamp;
            $current = Carbon::now()->timestamp;

            $timeLeftInSeconds = 0;

            if (! now()->gt($queue->completed_at)) {
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
                    'kingdom_name' => $kingdom->name.'(X/Y: '.$kingdom->x_position.'/'.$kingdom->y_position.')',
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
     * Retrieve other kingdoms owned by the character.
     */
    private function getOtherKingdoms(Character $character, Kingdom $kingdom): EloquentCollection
    {
        return $character->kingdoms()->where('id', '!=', $kingdom->id)->where('game_map_id', $kingdom->game_map_id)->get();
    }

    /**
     * Fetch buildings data from other kingdoms for upgrades or repairs.
     *
     * @param  EloquentCollection  $kingdoms
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
     * Fetch valid buildings.
     *
     * Fetch buildings who are:
     *
     * - Not locked
     * - Not currently in queue from manual upgrade
     * - Not currently i the capital city queue.
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
            ->whereNotIn('kingdom_buildings.id', function ($query) use ($kingdom) {
                $query->select(DB::raw('JSON_UNQUOTE(JSON_EXTRACT(building_request_data, "$[0].building_id"))'))
                    ->from('capital_city_building_queues')
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
     */
    private function getSelectableKingdoms(Kingdom $kingdom): array
    {
        $kingdoms = Kingdom::where('id', '!=', $kingdom->id)
            ->where('character_id', $kingdom->character_id)
            ->where('game_map_id', $kingdom->game_map_id)
            ->whereDoesntHave('unitsQueue')
            ->with('gameMap:id,name') // Eager load only id and name
            ->select('name', 'id', 'game_map_id')
            ->get()
            ->each(function ($kingdom) {
                $kingdom->game_map_name = $kingdom->gameMap->name;
                $kingdom->makeHidden(['gameMap']); // Hide the gameMap relationship
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
            return in_array($unit->id, $invalidUnitIds);
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
        return 'Your kingdom: '.$kingdom->name.' on plane: '.$kingdom->gameMap->name.' is now a capital city. '.
            'You can manage all your cities on this plane from this kingdom. This kingdom will also appear at the top '.
            'of your kingdom list with a special icon.';
    }
}
