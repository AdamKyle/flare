<?php

namespace App\Game\Maps\Services\Common;

use App\Flare\Models\Character;
use App\Flare\Models\Location;
use App\Flare\Models\ScheduledEvent;
use App\Game\Maps\Events\UpdateMonsterList;
use App\Game\Maps\Events\UpdateRaidMonsters;

trait UpdateRaidMonstersForLocation
{
    /**
     * Updates the monster list when a player enters a Location, preferring an active Raid,
     * then the current contextual (Weekly/Location Gem/normal) Monster list.
     */
    public function updateMonstersList(Character $character, ?Location $location = null): void
    {
        if (is_null($character->map)) {
            return;
        }

        if ($this->updateMonstersForRaid($character, $location)) {
            return;
        }

        $monsters = $this->monsterListService->getMonstersForCharacterAsList($character);

        event(new UpdateMonsterList($monsters, $character->user));
        event(new UpdateRaidMonsters([], $character->user));
    }

    /**
     * Update Monsters for a possible raid at a possible location for a character.
     */
    private function updateMonstersForRaid(Character $character, ?Location $location = null): bool
    {
        if (is_null($location)) {
            return false;
        }

        $currentGameMapId = $location->map->id;

        $raidEvents = ScheduledEvent::where('currently_running', true)->whereNotNull('raid_id')->get();

        foreach ($raidEvents as $raidEvent) {
            $raidBossLocationId = $raidEvent->raid->raid_boss_location_id;
            $corruptedLocationIds = $raidEvent->raid->corrupted_location_ids;

            $anchorLocationId = $raidBossLocationId;

            if (is_null($anchorLocationId)) {
                $anchorLocationId = $corruptedLocationIds[0] ?? null;
            }

            if (is_null($anchorLocationId)) {
                continue;
            }

            $anchorLocation = Location::find($anchorLocationId);

            if (is_null($anchorLocation)) {
                continue;
            }

            $raidGameMapId = $anchorLocation->map->id;

            if ($raidGameMapId !== $currentGameMapId) {
                continue;
            }

            $locationIds = $corruptedLocationIds;

            if ($location->id !== $raidBossLocationId) {
                $index = array_search($raidBossLocationId, $locationIds, true);

                if ($index !== false) {
                    unset($locationIds[$index]);
                    $locationIds = array_values($locationIds);
                }
            }

            if (in_array($location->id, $locationIds)) {
                $raidMonsters = $raidEvent->raid->getMonstersForSelection($location->map, $locationIds);

                event(new UpdateRaidMonsters($raidMonsters, $character->user));

                return true;
            }
        }

        return false;
    }
}
