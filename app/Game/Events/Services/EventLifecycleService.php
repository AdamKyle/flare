<?php

namespace App\Game\Events\Services;

use App\Flare\Events\UpdateScheduledEvents;
use App\Flare\Models\Announcement;
use App\Flare\Models\Character;
use App\Flare\Models\Event;
use App\Flare\Models\Faction;
use App\Flare\Models\FactionLoyalty;
use App\Flare\Models\GameMap;
use App\Flare\Models\Location;
use App\Flare\Models\Raid;
use App\Flare\Models\RaidBoss;
use App\Flare\Models\RaidBossParticipation;
use App\Flare\Models\ScheduledEvent;
use App\Game\Automation\Exploration\Services\ExplorationAutomationService;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Core\Values\FactionLevel;
use App\Game\Events\Values\EventType;
use App\Game\Events\Values\ScheduledEventStatus;
use App\Game\Factions\FactionLoyalty\Services\FactionLoyaltyService;
use App\Game\Maps\Services\LocationService;
use App\Game\Maps\Services\TraverseService;
use App\Game\Maps\Services\UpdateRaidMonsters;
use App\Game\Maps\Values\MapName;
use App\Game\Messages\Events\DeleteAnnouncementEvent;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Quests\Services\BuildQuestCacheService;
use App\Game\Raids\Events\CorruptLocations;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Throwable;

class EventLifecycleService
{
    /**
     * @param LocationService $locationService
     * @param UpdateRaidMonsters $updateRaidMonsters
     * @param EventSchedulerService $eventSchedulerService
     * @param KingdomEventService $kingdomEventService
     * @param TraverseService $traverseService
     * @param ExplorationAutomationService $explorationAutomationService
     * @param BuildQuestCacheService $buildQuestCacheService
     * @param FactionLoyaltyService $factionLoyaltyService
     */
    public function __construct(
        private readonly LocationService $locationService,
        private readonly UpdateRaidMonsters $updateRaidMonsters,
        private readonly EventSchedulerService $eventSchedulerService,
        private readonly KingdomEventService $kingdomEventService,
        private readonly TraverseService $traverseService,
        private readonly ExplorationAutomationService $explorationAutomationService,
        private readonly BuildQuestCacheService $buildQuestCacheService,
        private readonly FactionLoyaltyService $factionLoyaltyService,
    ) {}

    /**
     * Complete every running schedule whose own runtime event has ended.
     *
     * @return void
     */
    public function completeNaturallyExpired(): void
    {
        ScheduledEvent::where('status', ScheduledEventStatus::RUNNING)
            ->get()
            ->each(function (ScheduledEvent $scheduledEvent) {
                $runtimeEvent = $this->resolveRuntimeEvent($scheduledEvent);

                if (! is_null($runtimeEvent) && (is_null($runtimeEvent->ends_at) || $runtimeEvent->ends_at > now())) {
                    return;
                }

                if (! is_null($runtimeEvent)) {
                    $this->teardownRuntimeEvent($scheduledEvent, $runtimeEvent);
                }

                $scheduledEvent->applyStatus(ScheduledEventStatus::COMPLETED);

                event(new UpdateScheduledEvents($this->eventSchedulerService->fetchEvents()));
            });
    }

    /**
     * Cancel the schedule immediately, cancelling a seasonal parent's running child raids first.
     *
     * @param ScheduledEvent $scheduledEvent
     * @return void
     */
    public function cancel(ScheduledEvent $scheduledEvent): void
    {
        $scheduledEvent = $scheduledEvent->fresh();

        if ($scheduledEvent->status()->isTerminal()) {
            return;
        }

        if ($scheduledEvent->children()->exists()) {
            $this->cancelSeasonalParent($scheduledEvent);

            event(new UpdateScheduledEvents($this->eventSchedulerService->fetchEvents()));

            return;
        }

        $this->cancelSingle($scheduledEvent);

        event(new UpdateScheduledEvents($this->eventSchedulerService->fetchEvents()));
    }

    /**
     * Cancel a seasonal parent's running child raids, invalidate its future children, then cancel the parent.
     *
     * @param ScheduledEvent $parent
     * @return void
     *
     * @throws RuntimeException
     */
    private function cancelSeasonalParent(ScheduledEvent $parent): void
    {
        $parent = ScheduledEvent::findOrFail($parent->id);

        $previousStatus = $parent->status;
        $parent->applyStatus(ScheduledEventStatus::CANCELLING);

        $now = now();

        $runningChildren = $parent->children()->runningNow($now)->get();

        try {
            foreach ($runningChildren as $child) {
                $this->cancelSingle($child);
            }
        } catch (Throwable $throwable) {
            $parent->applyStatus($previousStatus);

            throw $throwable;
        }

        $runningChildrenStillNotTerminal = ScheduledEvent::whereIn('id', $runningChildren->pluck('id'))
            ->whereNotIn('status', ScheduledEventStatus::terminalStatuses())
            ->exists();

        if ($runningChildrenStillNotTerminal) {
            $parent->applyStatus($previousStatus);

            throw new RuntimeException('Not every running child raid reached cancelled for scheduled event id: '.$parent->id.'. Parent left active.');
        }

        $parent->children()
            ->whereIn('status', [
                ScheduledEventStatus::SCHEDULED,
                ScheduledEventStatus::QUEUED,
                ScheduledEventStatus::STARTING,
                ScheduledEventStatus::CANCELLING,
            ])
            ->get()
            ->each(function (ScheduledEvent $futureChild) {
                Cache::forget('scheduled-event-dispatch:'.$futureChild->id);

                $futureChild->applyStatus(ScheduledEventStatus::CANCELLED, now());
            });

        $this->cancelSingle($parent);
    }

    /**
     * Cancel one schedule and tear down its runtime event when it has one.
     *
     * @param ScheduledEvent $scheduledEvent
     * @return void
     */
    private function cancelSingle(ScheduledEvent $scheduledEvent): void
    {
        $status = $scheduledEvent->status();

        if ($status->isScheduled() || $status->isQueued()) {
            Cache::forget('scheduled-event-dispatch:'.$scheduledEvent->id);

            $scheduledEvent->applyStatus(ScheduledEventStatus::CANCELLED, now());

            return;
        }

        if ($status->isStarting()) {
            $scheduledEvent->applyStatus(ScheduledEventStatus::CANCELLING);
        }

        $runtimeEvent = $this->resolveRuntimeEvent($scheduledEvent);

        if (! is_null($runtimeEvent)) {
            $this->teardownRuntimeEvent($scheduledEvent, $runtimeEvent);
        }

        $scheduledEvent->applyStatus(ScheduledEventStatus::CANCELLED, now());
    }

    /**
     * Resolve the runtime event owned by the schedule, falling back to a single unowned legacy match.
     *
     * @param ScheduledEvent $scheduledEvent
     * @return Event|null
     *
     * @throws RuntimeException
     */
    private function resolveRuntimeEvent(ScheduledEvent $scheduledEvent): ?Event
    {
        $event = Event::where('scheduled_event_id', $scheduledEvent->id)->first();

        if (! is_null($event)) {
            return $event;
        }

        $query = Event::where('type', $scheduledEvent->event_type)->whereNull('scheduled_event_id');

        if (! is_null($scheduledEvent->raid_id)) {
            $query->where('raid_id', $scheduledEvent->raid_id);
        }

        $candidates = $query->get();

        if ($candidates->count() > 1) {
            throw new RuntimeException('Ambiguous legacy runtime event match for scheduled event id: '.$scheduledEvent->id.'. Refusing to guess which event owns it.');
        }

        return $candidates->first();
    }

    /**
     * Tear down the runtime event for its event type.
     *
     * @param ScheduledEvent $scheduledEvent
     * @param Event $event
     * @return void
     */
    private function teardownRuntimeEvent(ScheduledEvent $scheduledEvent, Event $event): void
    {
        $eventType = new EventType($scheduledEvent->event_type);

        if ($eventType->isRaidEvent()) {
            $this->teardownRaid($scheduledEvent, $event);

            return;
        }

        if ($eventType->isWeeklyCurrencyDrops()) {
            event(new GlobalMessageEvent('Weekly currency drops have come to an end! Come back next sunday for another chance!'));

            $this->cleanUpEvent($event);

            return;
        }

        if ($eventType->isWeeklyCelestials()) {
            event(new GlobalMessageEvent('The Creator has managed to close the gates and lock the Celestials away behind the doors of Kalitorm! Come back next week for another chance at the hunt!'));

            $this->cleanUpEvent($event);

            return;
        }

        if ($eventType->isWeeklyFactionLoyaltyEvent()) {
            event(new GlobalMessageEvent('Weekly Faction Loyalty Event has come to an end. Next time Npc Tasks refresh from level up, they will be back to normal.'));

            $this->cleanUpEvent($event);

            return;
        }

        if ($eventType->isWinterEvent()) {
            $this->teardownWinterEvent($event);

            $this->buildQuestCacheService->buildQuestCache(true);
            $this->buildQuestCacheService->buildRaidQuestCache(true);

            return;
        }

        if ($eventType->isDelusionalMemoriesEvent()) {
            $this->teardownDelusionalEvent($event);

            $this->buildQuestCacheService->buildQuestCache(true);
            $this->buildQuestCacheService->buildRaidQuestCache(true);
        }
    }

    /**
     * End a raid, remove its runtime event, then rebuild the raid quest cache without it.
     *
     * @param ScheduledEvent $scheduledEvent
     * @param Event $event
     * @return void
     */
    private function teardownRaid(ScheduledEvent $scheduledEvent, Event $event): void
    {
        $raid = Raid::find($scheduledEvent->raid_id);

        event(new GlobalMessageEvent('The Raid: '.$raid->name.' is now ending! Don\'t worry, the raid will be back soon. Check the event calendar for the next time!'));

        $this->unCorruptLocations($raid);

        RaidBossParticipation::where('raid_id', $raid->id)->delete();

        RaidBoss::where('raid_id', $raid->id)->delete();

        $this->updateMonstersForCharactersAtRaidLocations($raid);

        $this->cleanUpEvent($event);

        $this->buildQuestCacheService->buildRaidQuestCache(true);
    }

    /**
     * End the Winter event and return its characters to the Surface.
     *
     * @param Event $event
     * @return void
     */
    private function teardownWinterEvent(Event $event): void
    {
        $this->kingdomEventService->handleKingdomRewardsForEvent(MapName::ICE_PLANE->value);

        $gameMap = GameMap::where('name', MapName::ICE_PLANE->value)->first();
        $faction = Faction::where('game_map_id', $gameMap->id)->first();
        $surfaceMap = GameMap::where('name', MapName::SURFACE->value)->first();

        $this->resetCharactersOnEventMap($gameMap, $surfaceMap, $faction);

        if (! is_null($faction)) {
            FactionLoyalty::where('faction_id', $faction->id)
                ->where('is_pledged', true)
                ->chunk(100, function ($pledgedLoyalties) use ($faction) {
                    foreach ($pledgedLoyalties as $pledgedLoyalty) {
                        $this->unpledgeFromTheMapsFaction($pledgedLoyalty->character, $faction);
                    }
                });
        }

        event(new GlobalMessageEvent('The Queen of Ice calls forth her twisted memories and magics to seal the gates to her realm. "My son! You have stolen the memories of my son!" She bellows as she banishes you and others from her realm!'));

        $this->cleanUpEvent($event);

        $this->updateAllCharacterStatuses();
    }

    /**
     * End the Delusional Memories event and return its characters to the Surface.
     *
     * @param Event $event
     * @return void
     */
    private function teardownDelusionalEvent(Event $event): void
    {
        $this->kingdomEventService->handleKingdomRewardsForEvent(MapName::DELUSIONAL_MEMORIES->value);

        $gameMap = GameMap::where('name', MapName::DELUSIONAL_MEMORIES->value)->first();
        $faction = Faction::where('game_map_id', $gameMap->id)->first();
        $surfaceMap = GameMap::where('name', MapName::SURFACE->value)->first();

        $this->resetCharactersOnEventMap($gameMap, $surfaceMap, $faction);

        if (! is_null($faction)) {
            FactionLoyalty::where('faction_id', $faction->id)
                ->where('is_pledged', true)
                ->chunk(100, function ($pledgedLoyalties) use ($faction) {
                    foreach ($pledgedLoyalties as $pledgedLoyalty) {
                        $this->unpledgeFromTheMapsFaction($pledgedLoyalty->character, $faction);
                    }
                });
        }

        event(new GlobalMessageEvent('The voice of Fliniguss echos in your ears: "Child, I grow weary of your games." The twisted mother laughs: Ooooh hooo hooo hoo. A chill falls in the air.'));

        $this->cleanUpEvent($event);

        $this->updateAllCharacterStatuses();
    }

    /**
     * Stop exploration, reset event-map factions and move every character on the event map to the Surface.
     *
     * @param GameMap $gameMap
     * @param GameMap $surfaceMap
     * @param Faction|null $faction
     * @return void
     */
    private function resetCharactersOnEventMap(GameMap $gameMap, GameMap $surfaceMap, ?Faction $faction): void
    {
        Character::select('characters.*')
            ->join('maps', 'maps.character_id', '=', 'characters.id')
            ->where('maps.game_map_id', $gameMap->id)
            ->chunk(100, function ($characters) use ($surfaceMap, $faction, $gameMap) {
                foreach ($characters as $character) {
                    $this->explorationAutomationService->stopExploration($character);

                    $character->factions()->where('game_map_id', $gameMap->id)->update([
                        'current_level' => 0,
                        'current_points' => 0,
                        'points_needed' => FactionLevel::getPointsNeeded(0),
                        'maxed' => false,
                        'title' => null,
                    ]);

                    $this->traverseService->travel($surfaceMap->id, $character);

                    $this->unpledgeFromTheMapsFaction($character, $faction);
                }
            });
    }

    /**
     * Delete the runtime event and its announcement.
     *
     * @param Event $event
     * @return void
     */
    private function cleanUpEvent(Event $event): void
    {
        $announcement = Announcement::where('event_id', $event->id)->first();

        if (! is_null($announcement)) {
            event(new DeleteAnnouncementEvent($announcement->id));

            $announcement->delete();
        }

        $event->delete();
    }

    /**
     * Broadcast a status update for every character.
     *
     * @return void
     */
    private function updateAllCharacterStatuses(): void
    {
        Character::chunkById(250, function ($characters) {
            foreach ($characters as $character) {
                event(new UpdateCharacterStatus($character));
            }
        });
    }

    /**
     * Remove the character's pledge to the event map's Faction and stop any NPC they are assisting.
     *
     * @param Character $character
     * @param Faction|null $faction
     * @return void
     */
    private function unpledgeFromTheMapsFaction(Character $character, ?Faction $faction = null): void
    {
        if (is_null($faction)) {
            return;
        }

        $factionLoyalty = $character->factionLoyalties()
            ->where('faction_id', $faction->id)
            ->first();

        if (is_null($factionLoyalty)) {
            return;
        }

        $assistingNpc = $factionLoyalty
            ->factionLoyaltyNpcs()
            ->where('currently_helping', true)
            ->first();

        if (! is_null($assistingNpc)) {
            $this->factionLoyaltyService->stopAssistingNpc($character, $assistingNpc);
        }

        $this->factionLoyaltyService->removePledge($character, $faction);
    }

    /**
     * Clear the raid's corruption from its Locations and broadcast the corrupted Location data.
     *
     * @param Raid $raid
     * @return void
     */
    private function unCorruptLocations(Raid $raid): void
    {
        $raidLocations = [...$raid->corrupted_location_ids, $raid->raid_boss_location_id];

        Location::whereIn('id', $raidLocations)->update([
            'is_corrupted' => false,
            'raid_id' => null,
            'has_raid_boss' => false,
        ]);

        foreach (Location::whereIn('id', $raidLocations)->pluck('game_map_id')->unique()->all() as $gameMapId) {
            Cache::forget('map-locations-'.$gameMapId);
        }

        event(new CorruptLocations($this->locationService->fetchCorruptedLocationData($raid)));
    }

    /**
     * Refresh the monster list for every character standing at one of the raid's Locations.
     *
     * @param Raid $raid
     * @return void
     */
    private function updateMonstersForCharactersAtRaidLocations(Raid $raid): void
    {
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
                $this->updateRaidMonsters->updateMonstersForRaidLocations($character, $location);
            }
        }
    }
}
