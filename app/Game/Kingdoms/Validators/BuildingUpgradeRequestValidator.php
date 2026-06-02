<?php

namespace App\Game\Kingdoms\Validators;

use App\Flare\Models\BuildingInQueue;
use App\Flare\Models\CapitalCityBuildingQueue;
use App\Flare\Models\KingdomBuilding;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;

class BuildingUpgradeRequestValidator
{
    public function validate(array $requestData, string $requestType): ?string
    {
        foreach ($requestData as $request) {
            if (! is_array($request) || ! array_key_exists('kingdomId', $request) || ! array_key_exists('buildingIds', $request)) {
                continue;
            }

            $buildingIds = $request['buildingIds'];
            $kingdomId = (int) $request['kingdomId'];

            $buildings = KingdomBuilding::query()
                ->where('kingdom_id', $kingdomId)
                ->whereIn('id', $buildingIds)
                ->with('gameBuilding')
                ->get();

            if ($requestType === 'upgrade' && $buildings->contains(fn(KingdomBuilding $building) => $building->level >= $building->gameBuilding->max_level)) {
                return 'One or more buildings are already max level.';
            }

            if ($requestType === 'upgrade' && $buildings->contains(fn(KingdomBuilding $building) => $building->current_durability < $building->max_durability)) {
                return 'One or more buildings must be repaired before they can be upgraded.';
            }

            if ($this->hasActiveManualBuildingQueue($kingdomId, $buildingIds) ||
                $this->hasActiveCapitalCityBuildingQueue($kingdomId, $buildingIds)
            ) {
                if ($requestType === 'upgrade') {
                    return 'One or more buildings are already queued for upgrade.';
                }

                return 'One or more buildings are already queued.';
            }
        }

        return null;
    }

    private function hasActiveManualBuildingQueue(int $kingdomId, array $buildingIds): bool
    {
        return BuildingInQueue::query()
            ->where('kingdom_id', $kingdomId)
            ->whereIn('building_id', $buildingIds)
            ->exists();
    }

    private function hasActiveCapitalCityBuildingQueue(int $kingdomId, array $buildingIds): bool
    {
        return CapitalCityBuildingQueue::query()
            ->where('kingdom_id', $kingdomId)
            ->whereNotIn('status', [
                CapitalCityQueueStatus::REJECTED,
                CapitalCityQueueStatus::FINISHED,
                CapitalCityQueueStatus::CANCELLED,
            ])
            ->get()
            ->contains(function (CapitalCityBuildingQueue $queue) use ($buildingIds) {
                return collect($queue->building_request_data)
                    ->contains(function (array $request) use ($buildingIds) {
                        return in_array((int) $request['building_id'], $buildingIds, true) &&
                            ! in_array($request['secondary_status'], [
                                CapitalCityQueueStatus::REJECTED,
                                CapitalCityQueueStatus::FINISHED,
                                CapitalCityQueueStatus::CANCELLED,
                            ], true);
                    });
            });
    }
}
