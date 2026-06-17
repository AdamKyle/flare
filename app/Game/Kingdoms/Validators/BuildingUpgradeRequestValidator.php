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
        $validRequests = array_values(array_filter(
            $requestData,
            fn ($r) => is_array($r) && array_key_exists('kingdomId', $r) && array_key_exists('buildingIds', $r)
        ));

        if (empty($validRequests)) {
            return null;
        }

        $allKingdomIds = array_unique(array_map(fn ($r) => (int) $r['kingdomId'], $validRequests));
        $allBuildingIds = array_unique(array_merge(...array_map(fn ($r) => $r['buildingIds'], $validRequests)));

        $buildingsByKingdom = KingdomBuilding::query()
            ->whereIn('kingdom_id', $allKingdomIds)
            ->whereIn('id', $allBuildingIds)
            ->with('gameBuilding')
            ->get()
            ->groupBy('kingdom_id');

        foreach ($validRequests as $request) {
            $kingdomId = (int) $request['kingdomId'];
            $requestedBuildingIds = array_map(fn ($buildingId) => (int) $buildingId, $request['buildingIds']);
            $foundBuildingIds = $buildingsByKingdom->get($kingdomId, collect())->pluck('id')->map(fn ($buildingId) => (int) $buildingId)->all();

            if (! empty(array_diff($requestedBuildingIds, $foundBuildingIds))) {
                return 'Invalid request.';
            }
        }

        $manualQueueBlockedSet = [];
        BuildingInQueue::query()
            ->whereIn('kingdom_id', $allKingdomIds)
            ->whereIn('building_id', $allBuildingIds)
            ->where(function ($query) {
                $query->whereNull('completed_at')
                    ->orWhere('completed_at', '>', now());
            })
            ->get(['kingdom_id', 'building_id'])
            ->each(function ($row) use (&$manualQueueBlockedSet) {
                $manualQueueBlockedSet[$row->kingdom_id . ':' . $row->building_id] = true;
            });

        $capitalCityBlockedSet = [];
        CapitalCityBuildingQueue::query()
            ->whereIn('kingdom_id', $allKingdomIds)
            ->whereNotIn('status', [
                CapitalCityQueueStatus::REJECTED,
                CapitalCityQueueStatus::FINISHED,
                CapitalCityQueueStatus::CANCELLED,
                CapitalCityQueueStatus::CANCELLATION_REJECTED,
            ])
            ->get()
            ->each(function (CapitalCityBuildingQueue $queue) use (&$capitalCityBlockedSet) {
                foreach ($queue->building_request_data as $request) {
                    if (! in_array($request['secondary_status'], [
                        CapitalCityQueueStatus::REJECTED,
                        CapitalCityQueueStatus::FINISHED,
                        CapitalCityQueueStatus::CANCELLED,
                        CapitalCityQueueStatus::CANCELLATION_REJECTED,
                    ], true)) {
                        $capitalCityBlockedSet[$queue->kingdom_id . ':' . (int) $request['building_id']] = true;
                    }
                }
            });

        foreach ($validRequests as $request) {
            $buildingIds = $request['buildingIds'];
            $kingdomId = (int) $request['kingdomId'];
            $buildings = $buildingsByKingdom->get($kingdomId, collect());

            if ($requestType === 'upgrade' && $buildings->contains(fn (KingdomBuilding $building) => $building->level >= $building->gameBuilding->max_level)) {
                return 'One or more buildings are already max level.';
            }

            if ($requestType === 'upgrade' && $buildings->contains(fn (KingdomBuilding $building) => $building->current_durability < $building->max_durability)) {
                return 'One or more buildings must be repaired before they can be upgraded.';
            }

            foreach ($buildingIds as $buildingId) {
                $blockKey = $kingdomId . ':' . (int) $buildingId;

                if (isset($manualQueueBlockedSet[$blockKey]) || isset($capitalCityBlockedSet[$blockKey])) {
                    if ($requestType === 'upgrade') {
                        return 'One or more buildings are already queued for upgrade.';
                    }

                    return 'One or more buildings are already queued.';
                }
            }
        }

        return null;
    }
}
