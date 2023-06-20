<?php

namespace App\Console\Commands;

use App\Flare\Models\Raid;
use App\Flare\Models\Event;
use App\Flare\Models\Location;
use App\Flare\Models\Character;
use App\Flare\Values\EventType;
use Illuminate\Console\Command;
use App\Flare\Models\Announcement;
use App\Flare\Models\ScheduledEvent;
use App\Flare\Events\UpdateScheduledEvents;
use App\Game\Maps\Services\LocationService;
use App\Game\Raids\Events\CorruptLocations;
use App\Flare\Services\EventSchedulerService;
use App\Game\Maps\Services\UpdateRaidMonsters;
use App\Game\Messages\Events\GlobalMessageEvent;

class EndScheduledEvent extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'end:scheduled-event';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'End all scheduled events';

    /**
     * @param LocationService $locationService
     * @param UpdateRaidMonsters $updateRaidMonsters
     * @param EventSchedulerService $eventSchedulerService
     * @return void
     */
    public function handle(LocationService $locationService, UpdateRaidMonsters $updateRaidMonsters, EventSchedulerService $eventSchedulerService) {
        $targetEventStart = now()->copy()->addMinutes(5);

        $scheduledEvents = ScheduledEvent::where('end_date', '<=', now())->get();

        foreach ($scheduledEvents as $event) {
            $eventType = new EventType($event->event_type);

            if ($eventType->isRaidEvent()) {

                $this->endRaid($event->raid, $locationService, $updateRaidMonsters);

                $event->update([
                    'currently_running' => false,
                ]);

                event(new UpdateScheduledEvents($eventSchedulerService->fetchEvents()));
            }
        }
    }

    /**
     * End the raid.
     * 
     * - Un corrupt locations
     * - Delete Event for raid.
     * - Update monsters for locations, to set them back to normal.
     * - Cleanup other aspects such as announcements.
     *
     * @param Raid $raid
     * @param LocationService $locationService
     * @param UpdateRaidMonsters $updateRaidMonsters
     * @return void
     */
    protected function endRaid(Raid $raid, LocationService $locationService, UpdateRaidMonsters $updateRaidMonsters) {
        event(new GlobalMessageEvent('The Raid: ' . $raid->name . ' is now ending! Don\'t worry, the raid will be back soon. Check the event calendar for the next time!'));

        $this->unCorruptLocations($raid, $locationService);

        Event::where('raid_id', $raid->id)->delete();

        $this->updateMonstersForCharactersAtRaidLocations($raid, $updateRaidMonsters);

        $this->cleanUp();
    }

    /**
     * Set locations back to normal
     *
     * @param Raid $raid
     * @param LocationService $locationService
     * @return void
     */
    protected function unCorruptLocations(Raid $raid, LocationService $locationService) {
        $raidLocations = [...$raid->corrupted_location_ids, $raid->raid_boss_location_id];

        Location::whereIn('id', $raidLocations)->update([
            'is_corrupted'  => false,
            'raid_id'       => null,
            'has_raid_boss' => false,
        ]);

        event(new CorruptLocations($locationService->fetchCorruptedLocationData($raid)->toArray()));
    }

    /**
     * Cleanup other aspects of the raid.
     *
     * @return void
     */
    protected function cleanUp() {
        Announcement::where('expires_at', '<=', now())->delete();
    }

    /**
     * Update monsyers for the characters at raid locations.
     *
     * @param Raid $raid
     * @param UpdateRaidMonsters $updateRaidMonsters
     * @return void
     */
    protected function updateMonstersForCharactersAtRaidLocations(Raid $raid, UpdateRaidMonsters $updateRaidMonsters): void {
        $corruptedLocationIds = $raid->corrupted_location_ids;

        array_unshift($corruptedLocationIds, $raid->raid_boss_location_id);

        $corruptedLocations = Location::whereIn('id', $corruptedLocationIds)->get();

        foreach ($corruptedLocations as $location) {
            $characters = Character::leftJoin('maps', 'characters.id', '=', 'maps.character_id')
                ->where('maps.character_position_x', $location->x)
                ->where('maps.character_position_y', $location->y)
                ->where('maps.game_map_id', $location->game_map_id)
                ->get();

            foreach ($characters as $character) {
                $updateRaidMonsters->updateMonstersForRaidLocations($character, $location);
            }
        }
    }
}
