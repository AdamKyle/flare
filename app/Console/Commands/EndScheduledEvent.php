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
use App\Flare\Models\RaidBoss;
use App\Flare\Models\RaidBossParticipation;
use App\Game\Maps\Services\LocationService;
use App\Game\Raids\Events\CorruptLocations;
use App\Flare\Services\EventSchedulerService;
use App\Game\Maps\Services\UpdateRaidMonsters;
use App\Game\Messages\Events\DeleteAnnouncementEvent;
use App\Game\Messages\Events\GlobalMessageEvent;

class EndScheduledEvent extends Command {
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
        $this->endScheduledEvent($locationService, $updateRaidMonsters, $eventSchedulerService);
    }

    /**
     * End the scheduled events who are suppose to end.
     *
     * @param LocationService $locationService
     * @param UpdateRaidMonsters $updateRaidMonsters
     * @param EventSchedulerService $eventSchedulerService
     * @return void
     */
    protected function endScheduledEvent(LocationService $locationService, UpdateRaidMonsters $updateRaidMonsters, EventSchedulerService $eventSchedulerService): void {
        $scheduledEvents = ScheduledEvent::where('end_date', '<=', now())->get();

        foreach ($scheduledEvents as $event) {

            if (!$event->currently_running) {
                continue;
            }

            $eventType = new EventType($event->event_type);

            if ($eventType->isRaidEvent()) {

                $this->endRaid($event, $locationService, $updateRaidMonsters);

                $event->update([
                    'currently_running' => false,
                ]);

                event(new UpdateScheduledEvents($eventSchedulerService->fetchEvents()));
            }

            if ($eventType->isWeeklyCurrencyDrops()) {
                $this->endWeeklyCurrencyDrops();

                $event->update([
                    'currently_running' => false,
                ]);

                event(new UpdateScheduledEvents($eventSchedulerService->fetchEvents()));
            }

            if ($eventType->isWeeklyCelestials()) {
                $this->endWeeklySpawnEvent();

                $event->update([
                    'currently_running' => false,
                ]);

                event(new UpdateScheduledEvents($eventSchedulerService->fetchEvents()));
            }

            if ($eventType->isMonthlyPVP()) {
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
     * @param ScheduledEvent $event
     * @param LocationService $locationService
     * @param UpdateRaidMonsters $updateRaidMonsters
     * @return void
     */
    protected function endRaid(ScheduledEvent $event, LocationService $locationService, UpdateRaidMonsters $updateRaidMonsters) {

        $raid = $event->raid;

        event(new GlobalMessageEvent('The Raid: ' . $raid->name . ' is now ending! Don\'t worry, the raid will be back soon. Check the event calendar for the next time!'));

        $this->unCorruptLocations($raid, $locationService);

        $event = Event::where('raid_id', $raid->id)->first();

        RaidBossParticipation::where('raid_id', $raid->id)->delete();

        RaidBoss::where('raid_id', $raid->id)->delete();

        $this->updateMonstersForCharactersAtRaidLocations($raid, $updateRaidMonsters);

        $announcement = Announcement::where('event_id', $event->id)->first();

        event(new DeleteAnnouncementEvent($announcement->id));

        $announcement->delete();

        $event->delete();
    }

    /**
     * Ends a weekly currency event
     *
     * @param ScheduledEvent $event
     * @return void
     */
    protected function endWeeklyCurrencyDrops() {
        $event = Event::where('type', EventType::WEEKLY_CURRENCY_DROPS)->first();

        event(new GlobalMessageEvent('Weekly currency drops have come to an end! Come back next sunday for another chance!'));

        $announcement = Announcement::where('event_id', $event->id)->first();

        event(new DeleteAnnouncementEvent($announcement->id));

        $announcement->delete();

        $event->delete();
    }

    /**
     * End Weekly Celestial Spawn Event
     *
     * @param ScheduledEvent $event
     * @return void
     */
    protected function endWeeklySpawnEvent() {
        $event = Event::where('type', EventType::WEEKLY_CELESTIALS)->first();

        event(new GlobalMessageEvent('The Creator has managed to close the gates and lock the Celestials away behind the doors of Kalitorm! Come back next week for another chance at the hunt!'));

        $announcement = Announcement::where('event_id', $event->id)->first();

        event(new DeleteAnnouncementEvent($announcement->id));

        $announcement->delete();

        $event->delete();
    }

    /**
     * Set locations back to normal
     *
     * @param Raid $raid
     * @param LocationService $locationService
     * @return void
     */
    private function unCorruptLocations(Raid $raid, LocationService $locationService) {
        $raidLocations = [...$raid->corrupted_location_ids, $raid->raid_boss_location_id];

        Location::whereIn('id', $raidLocations)->update([
            'is_corrupted'  => false,
            'raid_id'       => null,
            'has_raid_boss' => false,
        ]);

        event(new CorruptLocations($locationService->fetchCorruptedLocationData($raid)->toArray()));
    }

    /**
     * Update monsters for the characters at raid locations.
     *
     * @param Raid $raid
     * @param UpdateRaidMonsters $updateRaidMonsters
     * @return void
     */
    private function updateMonstersForCharactersAtRaidLocations(Raid $raid, UpdateRaidMonsters $updateRaidMonsters): void {
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
