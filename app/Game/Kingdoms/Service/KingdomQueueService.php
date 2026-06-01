<?php

namespace App\Game\Kingdoms\Service;

use App\Flare\Models\BuildingExpansionQueue;
use App\Flare\Models\BuildingInQueue;
use App\Flare\Models\CapitalCityBuildingQueue;
use App\Flare\Models\CapitalCityUnitQueue;
use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Flare\Models\UnitInQueue;
use App\Flare\Models\UnitMovementQueue;
use App\Game\Kingdoms\Handlers\CapitalCityHandlers\CapitalCityKingdomLogHandler;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use App\Game\Kingdoms\Transformers\UnitMovementTransformer;
use App\Game\Core\Traits\ResponseBuilder;
use Illuminate\Support\Facades\Log;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;

class KingdomQueueService
{
    use ResponseBuilder;

    private Manager $manager;

    private UnitMovementTransformer $unitMovementTransformer;

    public function __construct(
        Manager $manager,
        UnitMovementTransformer $unitMovementTransformer,
        private readonly CapitalCityKingdomLogHandler $capitalCityKingdomLogHandler,
    ) {
        $this->manager = $manager;
        $this->unitMovementTransformer = $unitMovementTransformer;
    }

    public function fetchKingdomQueues(Kingdom $kingdom): array
    {
        $this->cleanOverdueCapitalCityQueues($kingdom);

        return [
            'building_queues' => $this->fetchBuildingQueues($kingdom),
            'unit_recruitment_queues' => $this->fetchUnitRecruitmentQueues($kingdom),
            'unit_movement_queues' => $this->fetchUnitMovementQueues($kingdom),
            'building_expansion_queues' => $this->fetchBuildingExpansionQueues($kingdom),
        ];
    }

    public function cleanOverdueCapitalCityBuildingQueuesForCharacter(Character $character, ?Kingdom $kingdom = null): void
    {
        CapitalCityBuildingQueue::query()
            ->where('character_id', $character->id)
            ->when($kingdom, function ($query) use ($kingdom) {
                return $query->whereHas('kingdom', function ($query) use ($kingdom) {
                    $query->where('game_map_id', $kingdom->game_map_id);
                })->where('kingdom_id', '!=', $kingdom->id);
            })
            ->whereIn('status', $this->activeBuildingStatuses())
            ->where('completed_at', '<', now())
            ->get()
            ->each(function (CapitalCityBuildingQueue $queue): void {
                $currentQueue = CapitalCityBuildingQueue::find($queue->id);

                if (is_null($currentQueue)) {
                    return;
                }

                $this->rejectOverdueBuildingQueue($currentQueue);
            });
    }

    protected function fetchBuildingQueues(Kingdom $kingdom): array
    {

        $buildingQueues = BuildingInQueue::where('kingdom_id', $kingdom->id)->get();

        $manualQueues = $buildingQueues->map(function ($buildingQueue) {
            return [
                'name' => $buildingQueue->building->gameBuilding->name,
                'id' => $buildingQueue->id,
                'from_level' => $buildingQueue->getType()->isUpgrading() ? $buildingQueue->building->level : null,
                'to_level' => $buildingQueue->getType()->isUpgrading() ? $buildingQueue->to_level : null,
                'type' => $buildingQueue->type_name,
                'time_remaining' => now()->diffInSeconds($buildingQueue->completed_at),
                'is_capital_city_managed' => ! is_null($buildingQueue->capital_city_building_queue_id),
                'capital_city_queue_id' => $buildingQueue->capital_city_building_queue_id,
            ];
        });

        $capitalCityQueues = CapitalCityBuildingQueue::query()
            ->where('kingdom_id', $kingdom->id)
            ->whereNotIn('status', [
                CapitalCityQueueStatus::REJECTED,
                CapitalCityQueueStatus::FINISHED,
                CapitalCityQueueStatus::CANCELLED,
                CapitalCityQueueStatus::CANCELLATION_REJECTED,
            ])
            ->get()
            ->flatMap(function (CapitalCityBuildingQueue $capitalCityBuildingQueue) {
                return collect($capitalCityBuildingQueue->building_request_data)
                    ->reject(function (array $request) {
                        return in_array($request['secondary_status'] ?? null, [
                            CapitalCityQueueStatus::REJECTED,
                            CapitalCityQueueStatus::FINISHED,
                            CapitalCityQueueStatus::CANCELLED,
                            CapitalCityQueueStatus::CANCELLATION_REJECTED,
                        ], true);
                    })
                    ->map(function (array $request) use ($capitalCityBuildingQueue) {
                        return [
                            'name' => $request['building_name'],
                            'id' => $capitalCityBuildingQueue->id,
                            'from_level' => $request['from_level'],
                            'to_level' => $request['to_level'],
                            'type' => $request['type'] === 'repair' ? 'Capital City Repair' : 'Capital City Upgrade',
                            'time_remaining' => now()->diffInSeconds($capitalCityBuildingQueue->completed_at),
                            'phase_status' => $capitalCityBuildingQueue->status,
                            'phase_timer_label' => $this->capitalCityBuildingPhaseTimerLabel($capitalCityBuildingQueue->status),
                            'is_capital_city_managed' => true,
                            'capital_city_queue_id' => $capitalCityBuildingQueue->id,
                        ];
                    });
            });

        return $manualQueues->toBase()->merge($capitalCityQueues)->values()->toArray();
    }

    private function capitalCityBuildingPhaseTimerLabel(string $status): string
    {
        return match ($status) {
            CapitalCityQueueStatus::TRAVELING => 'Traveling',
            CapitalCityQueueStatus::REPAIRING => 'Repairing',
            default => 'Building',
        };
    }

    protected function fetchBuildingExpansionQueues(Kingdom $kingdom): array
    {
        $buildingExpansionQueues = BuildingExpansionQueue::where('kingdom_id', $kingdom->id)->get();

        return $buildingExpansionQueues->map(function ($buildingExpansionQueue) {

            $fromSlot = 0;
            $toSlot = 1;

            $building = $buildingExpansionQueue->building;

            if (is_null($building)) {
                $this->logSkippedBuildingExpansionQueue($buildingExpansionQueue, 'missing_building');

                return null;
            }

            $buildingExpansion = $building->buildingExpansion;

            if (! is_null($buildingExpansion)) {
                $fromSlot = $buildingExpansion->expansion_count;
                $toSlot = $buildingExpansion->expansion_count + 1;
            }

            return [
                'name' => $building->name,
                'id' => $buildingExpansionQueue->id,
                'from_slot' => $fromSlot,
                'to_slot' => $toSlot,
                'time_remaining' => now()->diffInSeconds($buildingExpansionQueue->completed_at),
            ];
        })->filter()->values()->toArray();
    }

    private function logSkippedBuildingExpansionQueue(BuildingExpansionQueue $buildingExpansionQueue, string $reason): void
    {
        Log::warning('Skipping invalid building expansion queue.', [
            'building_expansion_queue_id' => $buildingExpansionQueue->id,
            'building_id' => $buildingExpansionQueue->building_id,
            'kingdom_id' => $buildingExpansionQueue->kingdom_id,
            'character_id' => $buildingExpansionQueue->character_id,
            'completed_at' => $buildingExpansionQueue->completed_at,
            'reason' => $reason,
        ]);
    }

    protected function fetchUnitRecruitmentQueues(Kingdom $kingdom): array
    {
        $unitsInQueue = UnitInQueue::where('kingdom_id', $kingdom->id)->get();

        $manualQueues = $unitsInQueue->map(function ($unitInQueue) {
            return [
                'name' => $unitInQueue->unit->name,
                'id' => $unitInQueue->id,
                'recruit_amount' => $unitInQueue->amount,
                'time_remaining' => now()->diffInSeconds($unitInQueue->completed_at),
                'is_capital_city_managed' => ! is_null($unitInQueue->capital_city_unit_queue_id),
                'capital_city_queue_id' => $unitInQueue->capital_city_unit_queue_id,
            ];
        });

        $capitalCityQueues = CapitalCityUnitQueue::query()
            ->where('kingdom_id', $kingdom->id)
            ->whereNotIn('status', [
                CapitalCityQueueStatus::REJECTED,
                CapitalCityQueueStatus::FINISHED,
                CapitalCityQueueStatus::CANCELLED,
                CapitalCityQueueStatus::CANCELLATION_REJECTED,
            ])
            ->get()
            ->flatMap(function (CapitalCityUnitQueue $capitalCityUnitQueue) {
                return collect($capitalCityUnitQueue->unit_request_data)
                    ->reject(function (array $request) {
                        return in_array($request['secondary_status'] ?? null, [
                            CapitalCityQueueStatus::REJECTED,
                            CapitalCityQueueStatus::FINISHED,
                            CapitalCityQueueStatus::CANCELLED,
                            CapitalCityQueueStatus::CANCELLATION_REJECTED,
                        ], true);
                    })
                    ->map(function (array $request) use ($capitalCityUnitQueue) {
                        return [
                            'name' => $request['name'],
                            'id' => $capitalCityUnitQueue->id,
                            'recruit_amount' => $request['amount'],
                            'time_remaining' => now()->diffInSeconds($capitalCityUnitQueue->completed_at),
                            'is_capital_city_managed' => true,
                            'capital_city_queue_id' => $capitalCityUnitQueue->id,
                            'type' => 'Capital City Recruitment',
                        ];
                    });
            });

        return $manualQueues->toBase()->merge($capitalCityQueues)->values()->toArray();
    }

    protected function fetchUnitMovementQueues(Kingdom $kingdom): array
    {
        $unitMovementQueues = UnitMovementQueue::where('to_kingdom_id', $kingdom->id)->orWhere('from_kingdom_id', $kingdom->id)->get();

        return $this->manager->createData(
            new Collection($unitMovementQueues, $this->unitMovementTransformer)
        )->toArray();
    }

    private function cleanOverdueCapitalCityQueues(Kingdom $kingdom): void
    {
        CapitalCityBuildingQueue::query()
            ->where('kingdom_id', $kingdom->id)
            ->whereIn('status', $this->activeBuildingStatuses())
            ->where('completed_at', '<', now())
            ->get()
            ->each(function (CapitalCityBuildingQueue $queue): void {
                $currentQueue = CapitalCityBuildingQueue::find($queue->id);

                if (is_null($currentQueue)) {
                    return;
                }

                $this->rejectOverdueBuildingQueue($currentQueue);
            });

        CapitalCityUnitQueue::query()
            ->where('kingdom_id', $kingdom->id)
            ->whereIn('status', [
                CapitalCityQueueStatus::TRAVELING,
                CapitalCityQueueStatus::BUILDING,
                CapitalCityQueueStatus::REPAIRING,
                CapitalCityQueueStatus::RECRUITING,
            ])
            ->where('completed_at', '<', now())
            ->get()
            ->each(function (CapitalCityUnitQueue $queue): void {
                $this->rejectOverdueUnitQueue($queue);
            });
    }

    private function activeBuildingStatuses(): array
    {
        return [
            CapitalCityQueueStatus::TRAVELING,
            CapitalCityQueueStatus::BUILDING,
            CapitalCityQueueStatus::REPAIRING,
        ];
    }

    private function rejectOverdueBuildingQueue(CapitalCityBuildingQueue $queue): void
    {
        Log::warning('Rejected overdue capital city queue.', [
            'queue_id' => $queue->id,
            'status' => $queue->status,
            'type' => 'building',
        ]);

        $requestData = collect($queue->building_request_data)->map(function (array $request): array {
            if (! in_array($request['secondary_status'] ?? null, [
                CapitalCityQueueStatus::FINISHED,
                CapitalCityQueueStatus::REJECTED,
                CapitalCityQueueStatus::CANCELLED,
                CapitalCityQueueStatus::CANCELLATION_REJECTED,
            ], true)) {
                $request['secondary_status'] = CapitalCityQueueStatus::REJECTED;
            }

            return $request;
        })->toArray();

        $queue->update([
            'building_request_data' => $requestData,
            'status' => CapitalCityQueueStatus::REJECTED,
        ]);

        $this->capitalCityKingdomLogHandler->possiblyCreateLogForBuildingQueue($queue->refresh());
    }

    private function rejectOverdueUnitQueue(CapitalCityUnitQueue $queue): void
    {
        Log::warning('Rejected overdue capital city queue.', [
            'queue_id' => $queue->id,
            'status' => $queue->status,
            'type' => 'unit',
        ]);

        $requestData = collect($queue->unit_request_data)->map(function (array $request): array {
            if (! in_array($request['secondary_status'] ?? null, [
                CapitalCityQueueStatus::FINISHED,
                CapitalCityQueueStatus::REJECTED,
                CapitalCityQueueStatus::CANCELLED,
                CapitalCityQueueStatus::CANCELLATION_REJECTED,
            ], true)) {
                $request['secondary_status'] = CapitalCityQueueStatus::REJECTED;
            }

            return $request;
        })->toArray();

        $queue->update([
            'unit_request_data' => $requestData,
            'status' => CapitalCityQueueStatus::REJECTED,
        ]);

        $this->capitalCityKingdomLogHandler->possiblyCreateLogForUnitQueue($queue->refresh());
    }
}
