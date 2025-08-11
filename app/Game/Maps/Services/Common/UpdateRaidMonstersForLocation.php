<?php

namespace App\Game\Maps\Services\Common;

use App\Flare\Models\Character;
use App\Flare\Models\Event;
use App\Flare\Models\Location;
use App\Flare\Values\ItemEffectsValue;
use App\Game\Maps\Events\UpdateMonsterList;
use App\Game\Maps\Events\UpdateRaidMonsters;
use App\Game\Messages\Events\ServerMessageEvent;
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

        $monsters = Cache::get('monsters')[$character->map->gameMap->name];

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
                $locationMonsters = Cache::get('monsters')[$location->name];

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
                    $monsters = $locationMonsters;

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
    protected function updateMonstersForRaid(Character $character, ?Location $location = null): bool
    {
        $raidEvent = Event::whereNotNull('raid_id')->first();
        if (! is_null($raidEvent) && ! is_null($location)) {
            $locationIds = array_map('intval', $raidEvent->raid->corrupted_location_ids);
            $raidBossLocationId = $raidEvent->raid->raid_boss_location_id;

            if ($location->id !== $raidBossLocationId) {
                $index = array_search($raidBossLocationId, $locationIds);

                if ($index !== false) {
                    unset($locationIds[$index]);
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
    protected function updateMonsterForLocationType(Character $character, ?Location $location = null): bool
    {
        if (is_null($location)) {
            return false;
        }

        $cache = Cache::get('special-location-monsters');

        if (! isset($cache['location-type-' . $location->type])) {
            return false;
        }

        $monsters = $cache['location-type-' . $location->type];

        event(new UpdateMonsterList($monsters, $character->user));
        event(new UpdateRaidMonsters([], $character->user));

        return true;
    }
}
