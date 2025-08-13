<?php

namespace App\Game\Events\Services;

use App\Flare\Models\Location;
use App\Flare\Models\Raid;
use App\Flare\Models\RaidBoss;
use App\Flare\Models\RaidBossParticipation;
use App\Game\Events\Services\Concerns\EventEnder;
use App\Game\Events\Values\EventType;
use App\Game\Maps\Services\LocationService;
use App\Game\Maps\Services\UpdateRaidMonsters;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Raids\Events\CorruptLocations;
use App\Flare\Models\Event as ActiveEvent;
use App\Flare\Models\ScheduledEvent;

class RaidEventEnderService implements EventEnder
{

    /**
     * @param  LocationService  $locationService
     * @param  UpdateRaidMonsters  $updateRaidMonsters
     * @param  AnnouncementCleanupService  $announcementCleanup
     */
    public function __construct(
        private readonly LocationService $locationService,
        private readonly UpdateRaidMonsters $updateRaidMonsters,
        private readonly AnnouncementCleanupService $announcementCleanup
    ) {}

    /**
     * @param  EventType  $type
     * @return bool
     */
    public function supports(EventType $type): bool
    {
        return $type->isRaidEvent();
    }

    /**
     * @param  EventType  $type
     * @param  ScheduledEvent  $scheduled
     * @param  ActiveEvent  $current
     * @return void
     */
    public function end(EventType $type, ScheduledEvent $scheduled, ActiveEvent $current): void
    {
        $raid = $scheduled->raid;

        if (is_null($raid)) {
            $this->announcementCleanup->deleteByEventId($current->id);
            $current->delete();
            return;
        }

        event(new GlobalMessageEvent('The Raid: ' . $raid->name . ' is now ending! Don\'t worry, the raid will be back soon. Check the event calendar for the next time!'));

        $this->unCorruptLocations($raid);

        $this->purgeRaidData($raid);

        $this->updateRaidMonstersForAffectedCharacters($raid);

        $this->announcementCleanup->deleteByEventId($current->id);

        $current->delete();
    }

    /**
     * @param  Raid  $raid
     * @return void
     */
    private function unCorruptLocations(Raid $raid): void
    {
        $ids = [...$raid->corrupted_location_ids, $raid->raid_boss_location_id];

        Location::query()
            ->whereIn('id', $ids)
            ->update([
                'is_corrupted' => false,
                'raid_id' => null,
                'has_raid_boss' => false,
            ]);

        event(new CorruptLocations($this->locationService->fetchCorruptedLocationData($raid)));
    }

    /**
     * @param  Raid  $raid
     * @return void
     */
    private function purgeRaidData(Raid $raid): void
    {
        RaidBossParticipation::query()->where('raid_id', $raid->id)->delete();

        RaidBoss::query()->where('raid_id', $raid->id)->delete();
    }

    /**
     * @param  Raid  $raid
     * @return void
     */
    private function updateRaidMonstersForAffectedCharacters(Raid $raid): void
    {
        $ids = $raid->corrupted_location_ids;
        array_unshift($ids, $raid->raid_boss_location_id);

        $locations = Location::query()
            ->whereIn('id', $ids)
            ->get();

        if ($locations->isEmpty()) {
            return;
        }

        foreach ($locations as $location) {
            $characters = \App\Flare\Models\Character::leftJoin('maps', 'characters.id', '=', 'maps.character_id')
                ->where('maps.character_position_x', $location->x)
                ->where('maps.character_position_y', $location->y)
                ->where('maps.game_map_id', $location->game_map_id)
                ->get(['characters.*']);

            if ($characters->isEmpty()) {
                continue;
            }

            foreach ($characters as $character) {
                $this->updateRaidMonsters->updateMonstersForRaidLocations($character, $location);
            }
        }
    }
}
