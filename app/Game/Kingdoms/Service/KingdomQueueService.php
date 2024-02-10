<?php

namespace App\Game\Kingdoms\Service;

use App\Flare\Models\BuildingExpansionQueue;
use App\Flare\Models\BuildingInQueue;
use App\Flare\Models\Kingdom;
use App\Flare\Models\UnitInQueue;
use App\Flare\Models\UnitMovementQueue;
use App\Flare\Transformers\UnitMovementTransformer;
use App\Game\Core\Traits\ResponseBuilder;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;

class KingdomQueueService {

    use ResponseBuilder;

    private Manager $manager;

    private UnitMovementTransformer $unitMovementTransformer;

    public function __construct(Manager $manager, UnitMovementTransformer $unitMovementTransformer) {
        $this->manager = $manager;
        $this->unitMovementTransformer = $unitMovementTransformer;
    }

    public function fetchKingdomQueues(Kingdom $kingdom): array {
        return [
            'building_queues' => $this->fetchBuildingQueues($kingdom),
            'unit_recruitment_queues' => $this->fetchUnitRecruitmentQueues($kingdom),
            'unit_movement_queues' => $this->fetchUnitMovementQueues($kingdom),
            'building_expansion_queues' => $this->fetchBuildingExpansionQueues($kingdom),
        ];
    }

    protected function fetchBuildingQueues(Kingdom $kingdom): array {

        $buildingQueues = BuildingInQueue::where('kingdom_id', $kingdom->id)->get();

        return $buildingQueues->map(function ($buildingQueue) {
            return [
                'name' => $buildingQueue->building->gameBuilding->name,
                'id'   => $buildingQueue->id,
                'from_level' => $buildingQueue->getType()->isUpgrading() ? $buildingQueue->building->level : null,
                'to_level' => $buildingQueue->getType()->isUpgrading() ? $buildingQueue->to_level : null,
                'type'=> $buildingQueue->type_name,
                'time_remaining' => now()->diffInSeconds($buildingQueue->completed_at),
            ];
        })->toArray();
    }

    protected function fetchBuildingExpansionQueues(Kingdom $kingdom): array {
        $buildingExpansionQueues = BuildingExpansionQueue::where('kingdom_id', $kingdom->id)->get();

        return $buildingExpansionQueues->map(function ($buildingExpansionQueue) {
            return [
                'name' => $buildingExpansionQueue->building->name,
                'id'   => $buildingExpansionQueue->id,
                'from_slot' => $buildingExpansionQueue->building->buildingExpansion->current_slot,
                'to_slot' => $buildingExpansionQueue->building->buildingExpansion->current_slot + 1,
                'time_remaining' => now()->diffInSeconds($buildingExpansionQueue->completed_at),
            ];
        })->toArray();
    }

    protected function fetchUnitRecruitmentQueues(Kingdom $kingdom): array {
        $unitsInQueue = UnitInQueue::where('kingdom_id', $kingdom->id)->get();

        return $unitsInQueue->map(function ($unitInQueue) {
            return [
                'name' => $unitInQueue->unit->name,
                'id'   => $unitInQueue->id,
                'recruit_amount' => $unitInQueue->amount,
                'time_remaining' => now()->diffInSeconds($unitInQueue->completed_at),
            ];
        })->toArray();
    }

    protected function fetchUnitMovementQueues(Kingdom $kingdom): array {
        $unitMovementQueues = UnitMovementQueue::where('to_kingdom_id', $kingdom->id)->orWhere('from_kingdom_id', $kingdom->id)->get();

        return $this->manager->createData(
            new Collection($unitMovementQueues, $this->unitMovementTransformer)
        )->toArray();
    }
}
