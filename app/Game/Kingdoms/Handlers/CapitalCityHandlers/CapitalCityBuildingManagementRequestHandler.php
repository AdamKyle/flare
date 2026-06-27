<?php

namespace App\Game\Kingdoms\Handlers\CapitalCityHandlers;

use App\Flare\Models\BuildingInQueue;
use App\Flare\Models\CapitalCityBuildingQueue;
use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Flare\Models\KingdomBuilding;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Kingdoms\Events\UpdateCapitalCityBuildingQueueRequest;
use App\Game\Kingdoms\Events\UpdateCapitalCityBuildingQueueTable;
use App\Game\Kingdoms\Events\UpdateCapitalCityBuildingUpgrades;
use App\Game\Kingdoms\Jobs\CapitalCityBuildingRequestMovement;
use App\Game\Kingdoms\Service\KingdomBuildingService;
use App\Game\Kingdoms\Service\UnitMovementService;
use App\Game\Kingdoms\Validators\BuildingUpgradeRequestValidator;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use App\Game\PassiveSkills\Values\PassiveSkillTypeValue;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class CapitalCityBuildingManagementRequestHandler
{
    use ResponseBuilder;

    public function __construct(
        private readonly KingdomBuildingService $kingdomBuildingService,
        private readonly UnitMovementService $unitMovementService,
        private readonly BuildingUpgradeRequestValidator $buildingUpgradeRequestValidator,
    ) {}

    public function createRequestQueue(Character $character, Kingdom $kingdom, array $requests, string $type): array
    {
        $character->loadMissing(['kingdoms', 'passiveSkills.passiveSkill']);

        $validationMessage = $this->buildingUpgradeRequestValidator->validate($requests, $type);

        if (! is_null($validationMessage)) {
            return $this->errorResult($validationMessage);
        }

        [$manualQueueBlockedSet, $capitalCityBlockedSet, $buildingsByKingdom] = $this->preloadRequestData($requests);

        $currentTime = now();
        $createdQueues = [];

        collect($requests)->each(function (array $request) use (
            $character, $kingdom, $type, $currentTime,
            $manualQueueBlockedSet, $capitalCityBlockedSet, $buildingsByKingdom, &$createdQueues
        ) {
            $kingdomId = $request['kingdomId'];
            $buildings = $buildingsByKingdom->get($kingdomId, collect());
            $toKingdom = $character->kingdoms->find($kingdomId);

            if (is_null($toKingdom)) {
                return;
            }

            $timeNeeded = $this->calculateTravelTime($character, $toKingdom, $kingdom);

            $buildingQueueData = $this->buildQueueData($buildings, $type, $manualQueueBlockedSet, $capitalCityBlockedSet);

            if (empty($buildingQueueData)) {
                return;
            }

            $travelTimeNeeded = $currentTime->clone()->addMinutes($timeNeeded);

            $capitalCityBuildingQueue = CapitalCityBuildingQueue::create([
                'kingdom_id' => $kingdomId,
                'requested_kingdom' => $kingdom->id,
                'building_request_data' => $buildingQueueData,
                'character_id' => $character->id,
                'status' => CapitalCityQueueStatus::TRAVELING,
                'messages' => [],
                'started_at' => $currentTime,
                'completed_at' => $travelTimeNeeded,
            ]);

            $dispatchTime = $travelTimeNeeded;

            if ($timeNeeded >= 15) {
                $dispatchTime = $currentTime->clone()->addMinutes(15);
            }

            Log::channel('capital_city_building_upgrades')->info('Dispatching Queue Movement', [
                '$capitalCityBuildingQueue' => $capitalCityBuildingQueue,
                '$character' => $character->id,
                '$kingdom' => $kingdom->id,
                '$dispatchTime' => $dispatchTime,
            ]);

            $this->dispatchQueueMovement($capitalCityBuildingQueue, $dispatchTime);

            $createdQueues[] = $capitalCityBuildingQueue;

            event(new UpdateCapitalCityBuildingQueueRequest(
                $character->user_id,
                false,
                $toKingdom->name.' processed.',
                'progress',
                $toKingdom->id,
                $toKingdom->name,
                $type,
                $this->formatQueueData($capitalCityBuildingQueue),
                collect($buildingQueueData)->pluck('building_id')->map(fn ($buildingId) => (int) $buildingId)->values()->toArray()
            ));
        });

        if (! empty($createdQueues)) {
            $this->sendOffEvents($character, $kingdom);
        }

        return $this->successResult([
            'message' => 'Building upgrades have been sent off to their respective kingdoms.
            The list below has been updated to reflect kingdoms you can send upgrade requests to. If
            you click: "Building Upgrade/Repair" in the top right, you will see a table of orders and
            their associated statuses.',
        ]);
    }

    private function formatQueueData(CapitalCityBuildingQueue $queue): array
    {
        $currentTime = now();
        $kingdom = $queue->kingdom;
        $totalTime = 0;

        if ($this->hasActiveTimer($queue->status) && $queue->completed_at->greaterThan($currentTime)) {
            $totalTime = $currentTime->diffInSeconds($queue->completed_at);
        }

        return [
            'kingdom_id' => $kingdom->id,
            'kingdom_name' => $kingdom->name,
            'map_name' => $kingdom->gameMap->name,
            'status' => $queue->status,
            'building_queue' => collect($queue->building_request_data)->map(function (array $request) {
                return [
                    'building_name' => $request['building_name'],
                    'secondary_status' => $request['secondary_status'],
                    'building_id' => (int) $request['building_id'],
                    'from_level' => $request['from_level'],
                    'to_level' => $request['to_level'],
                ];
            })->toArray(),
            'total_time' => $totalTime,
            'time_remaining' => $totalTime,
            'timer_duration' => (int) max(0, $queue->started_at->diffInSeconds($queue->completed_at)),
            'timer_started_at' => $queue->started_at->timestamp * 1000,
            'started_at' => $queue->started_at->toIso8601String(),
            'completed_at' => $queue->completed_at->toIso8601String(),
            'completed_at_timestamp' => $queue->completed_at->timestamp * 1000,
            'phase_timer_label' => $this->phaseTimerLabel($queue->status),
            'queue_id' => $queue->id,
        ];
    }

    private function hasActiveTimer(string $status): bool
    {
        return in_array($status, [
            CapitalCityQueueStatus::TRAVELING,
            CapitalCityQueueStatus::REQUESTING,
            CapitalCityQueueStatus::BUILDING,
            CapitalCityQueueStatus::REPAIRING,
        ], true);
    }

    private function phaseTimerLabel(string $status): string
    {
        return match ($status) {
            CapitalCityQueueStatus::TRAVELING => 'Traveling',
            CapitalCityQueueStatus::REQUESTING => 'Requesting Resources',
            CapitalCityQueueStatus::BUILDING => 'Building',
            CapitalCityQueueStatus::REPAIRING => 'Repairing',
            CapitalCityQueueStatus::PROCESSING => 'Processing',
            default => 'Processing',
        };
    }

    private function preloadRequestData(array $requests): array
    {
        $kingdomIds = [];
        $buildingIds = [];

        foreach ($requests as $request) {
            if (! is_array($request) || ! array_key_exists('kingdomId', $request) || ! array_key_exists('buildingIds', $request)) {
                continue;
            }
            $kingdomIds[] = (int) $request['kingdomId'];
            foreach ($request['buildingIds'] as $id) {
                $buildingIds[] = (int) $id;
            }
        }

        $kingdomIds = array_unique($kingdomIds);
        $buildingIds = array_unique($buildingIds);

        $buildingsByKingdom = empty($buildingIds)
            ? collect()
            : KingdomBuilding::whereIn('kingdom_id', $kingdomIds)
                ->whereIn('id', $buildingIds)
                ->with('gameBuilding')
                ->get()
                ->groupBy('kingdom_id');

        return [
            $this->buildManualQueueBlockedSet($kingdomIds, $buildingIds),
            $this->buildCapitalCityBlockedSet($kingdomIds),
            $buildingsByKingdom,
        ];
    }

    private function buildManualQueueBlockedSet(array $kingdomIds, array $buildingIds): array
    {
        if (empty($kingdomIds) || empty($buildingIds)) {
            return [];
        }

        $blocked = [];
        BuildingInQueue::whereIn('kingdom_id', $kingdomIds)
            ->whereIn('building_id', $buildingIds)
            ->where(function ($query) {
                $query->whereNull('completed_at')
                    ->orWhere('completed_at', '>', now());
            })
            ->get(['kingdom_id', 'building_id'])
            ->each(function ($row) use (&$blocked) {
                $blocked[$row->kingdom_id.':'.$row->building_id] = true;
            });

        return $blocked;
    }

    private function buildCapitalCityBlockedSet(array $kingdomIds): array
    {
        if (empty($kingdomIds)) {
            return [];
        }

        $blocked = [];
        CapitalCityBuildingQueue::whereIn('kingdom_id', $kingdomIds)
            ->whereNotIn('status', [
                CapitalCityQueueStatus::REJECTED,
                CapitalCityQueueStatus::FINISHED,
                CapitalCityQueueStatus::CANCELLED,
                CapitalCityQueueStatus::CANCELLATION_REJECTED,
            ])
            ->get()
            ->each(function (CapitalCityBuildingQueue $queue) use (&$blocked) {
                foreach ($queue->building_request_data as $request) {
                    if (! in_array($request['secondary_status'], [
                        CapitalCityQueueStatus::REJECTED,
                        CapitalCityQueueStatus::FINISHED,
                        CapitalCityQueueStatus::CANCELLED,
                        CapitalCityQueueStatus::CANCELLATION_REJECTED,
                    ], true)) {
                        $blocked[$queue->kingdom_id.':'.(int) $request['building_id']] = true;
                    }
                }
            });

        return $blocked;
    }

    private function calculateTravelTime(Character $character, Kingdom $toKingdom, Kingdom $fromKingdom): int
    {
        return $this->unitMovementService->getDistanceTime(
            $character,
            $toKingdom,
            $fromKingdom,
            PassiveSkillTypeValue::CAPITAL_CITY_REQUEST_BUILD_TRAVEL_TIME_REDUCTION
        );
    }

    private function buildQueueData(Collection $buildings, string $type, array $manualQueueBlockedSet, array $capitalCityBlockedSet): array
    {
        return $buildings->filter(function ($building) use ($type, $manualQueueBlockedSet, $capitalCityBlockedSet) {
            $blockKey = $building->kingdom_id.':'.$building->id;

            if (isset($manualQueueBlockedSet[$blockKey]) || isset($capitalCityBlockedSet[$blockKey])) {
                return false;
            }

            if ($type === 'upgrade') {
                return $building->level < $building->gameBuilding->max_level &&
                    $building->current_durability >= $building->max_durability;
            }

            return true;
        })->map(function ($building) use ($type) {
            $fromLevel = $type === 'upgrade' ? $building->level : null;
            $toLevel = $type === 'upgrade' ? $building->level + 1 : null;

            return [
                'building_id' => $building->id,
                'building_name' => $building->name,
                'costs' => $this->kingdomBuildingService->getBuildingCosts($building),
                'type' => $type,
                'missing_costs' => [],
                'secondary_status' => CapitalCityQueueStatus::TRAVELING,
                'from_level' => $fromLevel,
                'to_level' => $toLevel,
            ];
        })->toArray();
    }

    private function dispatchQueueMovement(CapitalCityBuildingQueue $queue, Carbon $dispatchTime): void
    {
        CapitalCityBuildingRequestMovement::dispatch($queue->id)->onConnection('long_running')->onQueue('default_long')->delay($dispatchTime);
    }

    private function sendOffEvents(Character $character, Kingdom $kingdom): void
    {
        event(new UpdateCapitalCityBuildingUpgrades($character, $kingdom));
        event(new UpdateCapitalCityBuildingQueueTable($character, $kingdom));
    }
}
