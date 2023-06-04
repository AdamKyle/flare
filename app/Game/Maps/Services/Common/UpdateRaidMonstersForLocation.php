<?php

namespace App\Game\Maps\Services\Common;

use App\Flare\Models\Event;
use App\Flare\Models\Location;
use App\Flare\Models\Character;
use App\Game\Maps\Events\UpdateRaidMonsters;

trait UpdateRaidMonstersForLocation {

    /**
     * Update Monsters for a possible raid at a possible location for a character.
     *
     * @param Character $character
     * @param Location|null $location
     * @return bool
     */
    public function updateMonstersForRaid(Character $character, ?Location $location = null): bool {
        $raidEvent = Event::whereNotNull('raid_id')->first();

        if (!is_null($raidEvent) && !is_null($location)) {

            $raidLocations = $raidEvent->raid->raid_monster_ids;
                
            array_push($raidLocations, $raidEvent->raid->raid_boss_location_id);

            if (in_array($location->id, $raidLocations)) {
                event(new UpdateRaidMonsters($raidEvent->raid->getMonstersForSelection(), $character->user));

                return true;
            }
        }

        return false;
    }
}