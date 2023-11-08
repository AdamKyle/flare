<?php

namespace App\Game\Maps\Services\Common;

use App\Flare\Models\Event;
use App\Flare\Models\Location;
use App\Flare\Models\Character;
use App\Flare\Values\ItemEffectsValue;
use Illuminate\Support\Facades\Cache;
use App\Game\Maps\Events\UpdateMonsterList;
use App\Game\Maps\Events\UpdateRaidMonsters;
use App\Game\Messages\Events\ServerMessageEvent;

trait UpdateRaidMonstersForLocation {

    /**
     * Updates the monster list when a player enters a special location.
     *
     * @param Character $character
     * @param Location|null $location
     * @return void
     */
    public function updateMonstersList(Character $character, ?Location $location = null): void {

        $monsters = Cache::get('monsters')[$character->map->gameMap->name];

        if (!is_null($character->map->gameMap->only_for_event)) {
            $hasAccessToPurgatory = $character->inventory->slots->where('item.type', 'quest')->where('item.effect', ItemEffectsValue::PURGATORY)->count() > 0;

            if (!$hasAccessToPurgatory) {
                $monsters = $monsters['easier'];
            } else {
                $monsters = $monsters['regular'];
            }
        }

        if ($this->updateMonstersForRaid($character, $location)) {
            return;
        }

        if (!is_null($location)) {
            if (!is_null($location->enemy_strength_type)) {
                $monsters = Cache::get('monsters')[$location->name];

                event(new ServerMessageEvent(
                    $character->user,
                    'You have entered a special location.
                Special locations are places where only specific quest items can drop. You can click View Location Details
                to read more about the location and click the relevant help docs link in the modal to read more about special locations.
                Exploring here will NOT allow the location specific quest items to drop. Monsters here are stronger then outside the location.'
                ));
            }
        }

        event(new UpdateMonsterList($monsters, $character->user));
        event(new UpdateRaidMonsters([], $character->user));
    }

    /**
     * Update Monsters for a possible raid at a possible location for a character.
     *
     * @param Character $character
     * @param Location|null $location
     * @return bool
     */
    protected function updateMonstersForRaid(Character $character, ?Location $location = null): bool {
        $raidEvent = Event::whereNotNull('raid_id')->first();

        if (!is_null($raidEvent) && !is_null($location)) {
            $locationIds        = array_map('intval', $raidEvent->raid->corrupted_location_ids);
            $raidBossLocationId = $raidEvent->raid->raid_boss_location_id;

            if ($location->id !== $raidBossLocationId) {
                $index = array_search($raidBossLocationId, $locationIds);

                if ($index !== false) {
                    unset($locationIds[$index]);
                }
            }

            $raidMonsters = $raidEvent->raid->getMonstersForSelection($locationIds);

            event(new UpdateRaidMonsters($raidMonsters, $character->user));

            return true;
        }

        return false;
    }
}
