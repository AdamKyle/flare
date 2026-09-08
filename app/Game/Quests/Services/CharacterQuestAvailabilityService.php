<?php

namespace App\Game\Quests\Services;

use App\Flare\Models\Event;
use App\Flare\Models\GameMap;
use App\Flare\Models\Quest;
use App\Flare\Models\Raid;
use App\Game\Raids\Services\RaidMapConflictService;

class CharacterQuestAvailabilityService
{
    public function __construct(
        private readonly RaidMapConflictService $raidMapConflictService,
    ) {}

    /**
     * Filter the factual Quest browse options down to what is currently Character-visible.
     */
    public function browseOptions(array $factualOptions): array
    {
        $activeEventTypes = $this->activeEventTypes();
        $gameMapIds = collect($factualOptions['game_maps'] ?? [])->pluck('id')->all();

        $eventTypeByGameMapId = GameMap::query()
            ->whereIn('id', $gameMapIds)
            ->get(['id', 'only_during_event_type', 'default'])
            ->keyBy('id');

        $visibleGameMaps = collect($factualOptions['game_maps'] ?? [])
            ->filter(function (array $gameMap) use ($eventTypeByGameMapId, $activeEventTypes) {
                $onlyDuringEventType = $eventTypeByGameMapId->get($gameMap['id'])?->only_during_event_type;

                if (is_null($onlyDuringEventType)) {
                    return true;
                }

                return in_array($onlyDuringEventType, $activeEventTypes, true);
            })
            ->values();

        $visibleGameMapIds = $visibleGameMaps->pluck('id')->all();

        $activeRaidIds = $this->activeRaidIds();
        $activeRaidMapIds = $this->activeRaidMapIds($activeRaidIds);

        return [
            'default_game_map_id' => $this->resolveDefaultGameMapId(
                $factualOptions['default_game_map_id'] ?? null,
                $visibleGameMapIds,
                $eventTypeByGameMapId
            ),
            'game_maps' => $visibleGameMaps->all(),
            'active_raid_map_ids' => $activeRaidMapIds,
        ];
    }

    /**
     * Recursively filter the shared factual Quest tree by currently active runtime Events and Raids.
     */
    public function tree(array $nodes): array
    {
        $activeEventTypes = $this->activeEventTypes();
        $activeRaidIds = $this->activeRaidIds();

        return $this->filterNodes($nodes, $activeEventTypes, $activeRaidIds);
    }

    /**
     * Determine whether a single Quest is currently available to Characters.
     */
    public function isQuestAvailable(Quest $quest): bool
    {
        if (! is_null($quest->only_for_event) && ! in_array($quest->only_for_event, $this->activeEventTypes(), true)) {
            return false;
        }

        if (! is_null($quest->raid_id) && ! in_array($quest->raid_id, $this->activeRaidIds(), true)) {
            return false;
        }

        return true;
    }

    /**
     * Recursively filter Quest tree nodes by active Event type and active Raid id.
     */
    private function filterNodes(array $nodes, array $activeEventTypes, array $activeRaidIds): array
    {
        $filtered = [];

        foreach ($nodes as $node) {
            if (! $this->nodeIsAvailable($node, $activeEventTypes, $activeRaidIds)) {
                continue;
            }

            $node['children'] = $this->filterNodes($node['children'] ?? [], $activeEventTypes, $activeRaidIds);

            $filtered[] = $node;
        }

        return $filtered;
    }

    /**
     * Determine whether a single Quest tree node is currently available to Characters.
     */
    private function nodeIsAvailable(array $node, array $activeEventTypes, array $activeRaidIds): bool
    {
        $onlyForEvent = $node['only_for_event'] ?? null;

        if (! is_null($onlyForEvent) && ! in_array($onlyForEvent, $activeEventTypes, true)) {
            return false;
        }

        $raidId = $node['raid']['id'] ?? null;

        if (! is_null($raidId) && ! in_array($raidId, $activeRaidIds, true)) {
            return false;
        }

        return true;
    }

    /**
     * Resolve the visible default Game Map id, falling back to the database default or first visible map.
     */
    private function resolveDefaultGameMapId(?int $originalDefaultId, array $visibleGameMapIds, $eventTypeByGameMapId): ?int
    {
        if (! is_null($originalDefaultId) && in_array($originalDefaultId, $visibleGameMapIds, true)) {
            return $originalDefaultId;
        }

        $databaseDefaultId = collect($visibleGameMapIds)
            ->first(fn (int $id) => (bool) $eventTypeByGameMapId->get($id)?->default);

        if (! is_null($databaseDefaultId)) {
            return $databaseDefaultId;
        }

        return $visibleGameMapIds[0] ?? null;
    }

    /**
     * Resolve every runtime Event type that is currently active.
     *
     * @return int[]
     */
    private function activeEventTypes(): array
    {
        return Event::query()->pluck('type')->unique()->values()->all();
    }

    /**
     * Resolve every Raid id currently backed by a running runtime Event.
     *
     * @return int[]
     */
    private function activeRaidIds(): array
    {
        return Event::query()->whereNotNull('raid_id')->pluck('raid_id')->unique()->values()->all();
    }

    /**
     * Resolve every Game Map id occupied by a currently active runtime Raid.
     *
     * @param  int[]  $activeRaidIds
     * @return int[]
     */
    private function activeRaidMapIds(array $activeRaidIds): array
    {
        $mapIds = [];

        foreach (Raid::whereIn('id', $activeRaidIds)->get() as $raid) {
            $mapIds = array_merge($mapIds, $this->raidMapConflictService->mapIdsForRaid($raid));
        }

        return array_values(array_unique($mapIds));
    }
}
