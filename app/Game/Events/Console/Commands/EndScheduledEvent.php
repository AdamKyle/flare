<?php

namespace App\Game\Events\Console\Commands;

use App\Flare\Models\Raid;
use App\Flare\Models\Event;
use App\Flare\Models\Location;
use App\Flare\Models\Character;
use App\Game\Core\Values\FactionLevel;
use App\Game\Events\Values\EventType;
use App\Game\Exploration\Services\ExplorationAutomationService;
use App\Game\Quests\Services\BuildQuestCacheService;
use Illuminate\Console\Command;
use App\Flare\Models\Announcement;
use App\Flare\Models\ScheduledEvent;
use App\Flare\Events\UpdateScheduledEvents;
use App\Flare\Models\GameMap;
use App\Flare\Models\RaidBoss;
use App\Flare\Models\RaidBossParticipation;
use App\Game\Maps\Services\LocationService;
use App\Game\Raids\Events\CorruptLocations;
use App\Flare\Services\EventSchedulerService;
use App\Flare\Values\MapNameValue;
use App\Game\Events\Services\KingdomEventService;
use App\Game\Maps\Services\TraverseService;
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
     * @param KingdomEventService $kingdomEventService,
     * @param TraverseService $traverseService,
     * @return void
     */
    public function handle(
        LocationService $locationService,
        UpdateRaidMonsters $updateRaidMonsters,
        EventSchedulerService $eventSchedulerService,
        KingdomEventService $kingdomEventService,
        TraverseService $traverseService,
        ExplorationAutomationService $explorationAutomationService,
        BuildQuestCacheService $buildQuestCacheService
    ) {
        $this->endScheduledEvent(
            $locationService,
            $updateRaidMonsters,
            $eventSchedulerService,
            $kingdomEventService,
            $traverseService,
            $explorationAutomationService,
            $buildQuestCacheService
        );
    }

    /**
     * End the scheduled events who are suppose to end.
     *
     * @param LocationService $locationService
     * @param UpdateRaidMonsters $updateRaidMonsters
     * @param EventSchedulerService $eventSchedulerService
     * @param KingdomEventService $kingdomEventService
     * @param TraverseService $traverseService
     * @param ExplorationAutomationService $explorationAutomationService
     * @return void
     * @throws \Exception
     */
    protected function endScheduledEvent(
        LocationService $locationService,
        UpdateRaidMonsters $updateRaidMonsters,
        EventSchedulerService $eventSchedulerService,
        KingdomEventService $kingdomEventService,
        TraverseService $traverseService,
        ExplorationAutomationService $explorationAutomationService,
        BuildQuestCacheService $buildQuestCacheService
    ): void {

        $scheduledEvents = ScheduledEvent::where('end_date', '<=', now())->get();
        $eventsToEnd     = Event::where('ends_at', '<=', now())->get();

        foreach ($scheduledEvents as $event) {

            if (!$event->currently_running) {
                continue;
            }

            $eventType = new EventType($event->event_type);

            if ($eventType->isRaidEvent()) {

                $this->endRaid($event, $locationService, $updateRaidMonsters);

                $buildQuestCacheService->buildRaidQuestCache(true);

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

            if ($eventType->isWinterEvent()) {
                $this->endWinterEvent($kingdomEventService, $traverseService, $explorationAutomationService);

                $buildQuestCacheService->buildQuestCache(true);
                $buildQuestCacheService->buildRaidQuestCache(true);

                $event->update([
                    'currently_running' => false,
                ]);

                event(new UpdateScheduledEvents($eventSchedulerService->fetchEvents()));
            }
        }

        /**
         * End Events
         */
        forEach ($eventsToEnd as $event) {
            $announcement = Announcement::where('event_id', $event->id)->first();

            if (is_null($announcement)) {
                continue;
            }

            event(new DeleteAnnouncementEvent($announcement->id));

            $announcement->delete();

            $event->delete();
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

        if (is_null($event)) {
            return;
        }

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

        if (is_null($event)) {
            return;
        }

        event(new GlobalMessageEvent('The Creator has managed to close the gates and lock the Celestials away behind the doors of Kalitorm! Come back next week for another chance at the hunt!'));

        $announcement = Announcement::where('event_id', $event->id)->first();

        event(new DeleteAnnouncementEvent($announcement->id));

        $announcement->delete();

        $event->delete();
    }

    /**
     * End the winter event.
     *
     * @param KingdomEventService $kingdomEventService
     * @param TraverseService $traverseService
     * @param ExplorationAutomationService $explorationAutomationService
     * @return void
     */
    protected function endWinterEvent(KingdomEventService $kingdomEventService,
                                      TraverseService $traverseService,
                                      ExplorationAutomationService $explorationAutomationService) {

        $event = Event::where('type', EventType::WINTER_EVENT)->first();

        if (is_null($event)) {
            return;
        }

        $kingdomEventService->handleKingdomRewardsForEvent(MapNameValue::ICE_PLANE);

        $gameMap    = GameMap::where('name', MapNameValue::ICE_PLANE)->first();
        $surfaceMap = GameMap::where('name', MapNameValue::SURFACE)->first();

        Character::join('maps', 'maps.character_id', '=', 'characters.id')
            ->where('maps.game_map_id', $gameMap->id)
            ->chunk(100, function ($characters) use ($traverseService, $surfaceMap, $explorationAutomationService, $gameMap) {
                foreach ($characters as $character) {
                    $explorationAutomationService->stopExploration($character);

                    $character->factions()->where('game_map_id', $gameMap->id)->update([
                        'current_level'  => 0,
                        'current_points' => 0,
                        'points_needed'  => FactionLevel::getPointsNeeded(0),
                        'maxed'          => false,
                        'title'          => null,
                    ]);

                    $traverseService->travel($surfaceMap->id, $character);
                }
            });

        event(new GlobalMessageEvent('The Queen of Ice calls forth her twisted memories and magics to seal the gates to her realm. "My son! You have stolen the memories of my son!" She bellows as she banishes you and others from her realm!'));

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
