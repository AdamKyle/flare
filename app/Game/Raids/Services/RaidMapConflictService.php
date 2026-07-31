<?php

namespace App\Game\Raids\Services;

use App\Flare\Models\GameMap;
use App\Flare\Models\Location;
use App\Flare\Models\Raid;
use App\Flare\Models\ScheduledEvent;
use App\Game\Events\Values\ScheduledEventStatus;
use Illuminate\Support\Collection;

class RaidMapConflictService
{
    /**
     * The unique game map ids a raid occupies: the raid boss location's map
     * plus every corrupted location's map.
     *
     * @return int[]
     */
    public function mapIdsForRaid(Raid $raid): array
    {
        $locationIds = array_filter(
            array_merge([$raid->raid_boss_location_id], $raid->corrupted_location_ids ?? [])
        );

        if (empty($locationIds)) {
            return [];
        }

        return Location::whereIn('id', $locationIds)
            ->pluck('game_map_id')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Every raid id that owns a location on any of the given maps.
     *
     * @param  int[]  $gameMapIds
     * @return int[]
     */
    public function raidIdsOnMaps(array $gameMapIds): array
    {
        if (empty($gameMapIds)) {
            return [];
        }

        $locationIdsOnMaps = Location::whereIn('game_map_id', $gameMapIds)->pluck('id')->all();

        if (empty($locationIdsOnMaps)) {
            return [];
        }

        $query = Raid::whereIn('raid_boss_location_id', $locationIdsOnMaps);

        foreach ($locationIdsOnMaps as $locationId) {
            $query->orWhereJsonContains('corrupted_location_ids', $locationId);
        }

        return $query->pluck('id')->all();
    }

    /**
     * The active scheduled event reserving one of the given raid's maps, if any.
     *
     * A raid id reserves its map(s) while its own scheduled event is queued,
     * starting, running, or cancelling, or while it is a scheduled child raid
     * waiting on a seasonal parent that is itself active.
     */
    public function findActiveConflict(Raid $raid, ?int $excludeScheduledEventId = null): ?ScheduledEvent
    {
        $gameMapIds = $this->mapIdsForRaid($raid);
        $raidIdsOnSameMaps = $this->raidIdsOnMaps($gameMapIds);

        if (empty($raidIdsOnSameMaps)) {
            return null;
        }

        return $this->reservingScheduledEventsQuery($raidIdsOnSameMaps, $excludeScheduledEventId)->first();
    }

    public function hasActiveConflict(Raid $raid, ?int $excludeScheduledEventId = null): bool
    {
        return ! is_null($this->findActiveConflict($raid, $excludeScheduledEventId));
    }

    /**
     * Every unordered pair index within the requested raid list whose map sets
     * intersect, keyed by "indexA:indexB".
     *
     * @param  Raid[]  $raids
     * @return array<string, array{0: int, 1: int}>
     */
    public function requestedSetConflicts(array $raids): array
    {
        $mapsByIndex = [];

        foreach ($raids as $index => $raid) {
            $mapsByIndex[$index] = $this->mapIdsForRaid($raid);
        }

        $conflicts = [];

        foreach ($mapsByIndex as $indexA => $mapsA) {
            foreach ($mapsByIndex as $indexB => $mapsB) {
                if ($indexA >= $indexB) {
                    continue;
                }

                if (! empty(array_intersect($mapsA, $mapsB))) {
                    $conflicts[$indexA.':'.$indexB] = [$indexA, $indexB];
                }
            }
        }

        return $conflicts;
    }

    /**
     * The comma-separated names of every map shared between two raids, for
     * building a factual conflict message.
     */
    public function sharedMapNames(Raid $raidA, Raid $raidB): string
    {
        $sharedMapIds = array_intersect($this->mapIdsForRaid($raidA), $this->mapIdsForRaid($raidB));

        return GameMap::whereIn('id', $sharedMapIds)->pluck('name')->implode(', ');
    }

    /**
     * @param  int[]  $raidIds
     */
    private function reservingScheduledEventsQuery(array $raidIds, ?int $excludeScheduledEventId = null): Collection
    {
        $query = ScheduledEvent::query()
            ->whereIn('raid_id', $raidIds)
            ->whereIn('status', ScheduledEventStatus::activeStatuses());

        if (! is_null($excludeScheduledEventId)) {
            $query->where('id', '!=', $excludeScheduledEventId);
        }

        return $query->get();
    }
}
