<?php

namespace App\Game\Maps\Services\Common;

use App\Flare\Models\Character;
use App\Flare\Models\Location;
use App\Flare\Models\ScheduledEvent;
use App\Flare\Values\ItemEffectsValue;
use App\Game\Maps\Events\UpdateMonsterList;
use App\Game\Maps\Events\UpdateRaidMonsters;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Monsters\Services\MonsterListService;
use Illuminate\Support\Facades\Cache;

trait UpdateRaidMonstersForLocation
{
    /**
     * Updates the monster list when a player enters a special location.
     */
    public function updateMonstersList(Character $character, ?Location $location = null): void
    {

        if (is_null($character->map)) {
            return;
        }

        $monsterListService = resolve(MonsterListService::class);

        $monsters = $monsterListService->getMonstersForCharacterAsList($character);

        $hasAccessToPurgatory = $character->inventory->slots->where('item.effect', ItemEffectsValue::PURGATORY)->count() > 0;

        if (! is_null($character->map->gameMap->only_during_event_type)) {
            if (! $hasAccessToPurgatory) {
                $monsters = $monsters['easier'];
            } else {
                $monsters = $monsters['regular'];
            }
        }

        if ($this->updateMonstersForRaid($character, $location)) {
            return;
        }

        if ($this->updateMonsterForLocationType($character, $location)) {
            return;
        }

        if (! is_null($location)) {
            if (! is_null($location->enemy_strength_increase)) {

                if (! $hasAccessToPurgatory && ! is_null($character->map->gameMap->only_during_event_type)) {
                    event(new ServerMessageEvent(
                        $character->user,
                        'You have entered a special location in a place that is hostile and dangerous. Alas because you are so squishy,
                        down here, at this location, you will only face regular critters. Fight on child! Become stronger! Special quest items can drop
                        from this location only through manual fighting! You can automate here if you please, but no quest items will drop from here. You can see what quest
                        items will drop by clicking or tapping View Location Details. Click or tap the help link and then click or tap special locations with in the help modal. Find this
                        location in the list on the help docs and open it to see the quest items.'
                    ));
                } else {

                    event(new ServerMessageEvent(
                        $character->user,
                        'You have entered a special location.
                Special locations are places where only specific quest items can drop. You can click View Location Details
                to read more about the location and click the relevant help docs link in the modal to read more about special locations.
                Exploring here will NOT allow the location specific quest items to drop. Monsters here are stronger then outside the location.'
                    ));
                }
            }
        }

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

    /**
     * Update the monsters list for a special location type, if it has monsters.
     */
    private function updateMonsterForLocationType(Character $character, ?Location $location = null): bool
    {
        if (is_null($location)) {
            return false;
        }

        $cache = Cache::get('special-location-monsters');

        if (! isset($cache['location-type-'.$location->type])) {
            return false;
        }

        $monsters = $cache['location-type-'.$location->type];

        event(new UpdateMonsterList($monsters, $character->user));
        event(new UpdateRaidMonsters([], $character->user));

        return true;
    }
}
