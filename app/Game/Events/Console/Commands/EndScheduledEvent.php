<?php

namespace App\Game\Events\Console\Commands;

use App\Flare\Models\Faction;
use App\Flare\Models\GlobalEventCraft;
use App\Flare\Models\GlobalEventCraftingInventorySlot;
use App\Flare\Models\GlobalEventEnchant;
use App\Flare\Models\GlobalEventGoal;
use App\Flare\Models\GlobalEventKill;
use App\Flare\Models\GlobalEventParticipation;
use App\Flare\Models\Raid;
use App\Flare\Models\Event;
use App\Flare\Models\Location;
use App\Flare\Models\Character;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Core\Values\FactionLevel;
use App\Game\Events\Values\EventType;
use App\Game\Exploration\Services\ExplorationAutomationService;
use App\Game\Factions\FactionLoyalty\Services\FactionLoyaltyService;
use App\Game\Quests\Services\BuildQuestCacheService;
use Exception;
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
     * @param KingdomEventService $kingdomEventService ,
     * @param TraverseService $traverseService ,
     * @param ExplorationAutomationService $explorationAutomationService
     * @param BuildQuestCacheService $buildQuestCacheService
     * @param FactionLoyaltyService $factionLoyaltyService
     * @return void
     * @throws Exception
     */
    public function handle(
        LocationService $locationService,
        UpdateRaidMonsters $updateRaidMonsters,
        EventSchedulerService $eventSchedulerService,
        KingdomEventService $kingdomEventService,
        TraverseService $traverseService,
        ExplorationAutomationService $explorationAutomationService,
        BuildQuestCacheService $buildQuestCacheService,
        FactionLoyaltyService $factionLoyaltyService,
    ): void
    {
        $this->endScheduledEvent(
            $locationService,
            $updateRaidMonsters,
            $eventSchedulerService,
            $kingdomEventService,
            $traverseService,
            $explorationAutomationService,
            $buildQuestCacheService,
            $factionLoyaltyService
        );
    }

    /**
     * End the scheduled events who are supposed to end.
     *
     * @param LocationService $locationService
     * @param UpdateRaidMonsters $updateRaidMonsters
     * @param EventSchedulerService $eventSchedulerService
     * @param KingdomEventService $kingdomEventService
     * @param TraverseService $traverseService
     * @param ExplorationAutomationService $explorationAutomationService
     * @param BuildQuestCacheService $buildQuestCacheService
     * @param FactionLoyaltyService $factionLoyaltyService
     * @return void
     * @throws Exception
     */
    protected function endScheduledEvent(
        LocationService $locationService,
        UpdateRaidMonsters $updateRaidMonsters,
        EventSchedulerService $eventSchedulerService,
        KingdomEventService $kingdomEventService,
        TraverseService $traverseService,
        ExplorationAutomationService $explorationAutomationService,
        BuildQuestCacheService $buildQuestCacheService,
        FactionLoyaltyService $factionLoyaltyService
    ): void {

        $scheduledEvents = ScheduledEvent::where('end_date', '<=', now())->get();

        foreach ($scheduledEvents as $event) {

            $currentEvent = Event::where('type', $event->event_type)->where('ends_at', '<=', now())->first();

            if (is_null($currentEvent)) {

                $event->update([
                    'currently_running' => false
                ]);

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
                $this->endWeeklyCurrencyDrops($currentEvent);

                $event->update([
                    'currently_running' => false,
                ]);

                event(new UpdateScheduledEvents($eventSchedulerService->fetchEvents()));
            }

            if ($eventType->isWeeklyCelestials()) {
                $this->endWeeklySpawnEvent($currentEvent);

                $event->update([
                    'currently_running' => false,
                ]);

                event(new UpdateScheduledEvents($eventSchedulerService->fetchEvents()));
            }

            if ($eventType->isWeeklyFactionLoyaltyEvent()) {
                $this->endWeeklyFactionLoyaltyEvent($currentEvent);

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
                $this->endWinterEvent($kingdomEventService, $traverseService, $explorationAutomationService, $factionLoyaltyService, $currentEvent);

                $buildQuestCacheService->buildQuestCache(true);
                $buildQuestCacheService->buildRaidQuestCache(true);

                $event->update([
                    'currently_running' => false,
                ]);

                event(new UpdateScheduledEvents($eventSchedulerService->fetchEvents()));
            }

            if ($eventType->isDelusionalMemoriesEvent()) {
                $this->endDelusionalEvent($kingdomEventService, $traverseService, $explorationAutomationService, $factionLoyaltyService, $currentEvent);

                $buildQuestCacheService->buildQuestCache(true);
                $buildQuestCacheService->buildRaidQuestCache(true);

                $event->update([
                    'currently_running' => false,
                ]);

                event(new UpdateScheduledEvents($eventSchedulerService->fetchEvents()));
            }

            $announcement = Announcement::where('event_id', $currentEvent->id)->first();

            if (is_null($announcement)) {
                continue;
            }

            event(new DeleteAnnouncementEvent($announcement->id));

            $announcement->delete();

            $currentEvent->delete();
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
     * @param Event $event
     * @return void
     */
    protected function endWeeklyCurrencyDrops(Event $event): void {

        event(new GlobalMessageEvent('Weekly currency drops have come to an end! Come back next sunday for another chance!'));

        $announcement = Announcement::where('event_id', $event->id)->first();

        event(new DeleteAnnouncementEvent($announcement->id));

        $announcement->delete();

        $event->delete();
    }

    /**
     * End Faction Loyalty Event.
     *
     * @param Event $event
     * @return void
     */
    protected function endWeeklyFactionLoyaltyEvent(Event $event): void {

        event(new GlobalMessageEvent('Weekly Faction Loyalty Event has come to an end. Next time Npc Tasks refresh from level up, they will be back to normal.'));

        $announcement = Announcement::where('event_id', $event->id)->first();

        event(new DeleteAnnouncementEvent($announcement->id));

        $announcement->delete();

        $event->delete();
    }

    /**
     * End Weekly Celestial Spawn Event
     *
     * @param Event $event
     * @return void
     */
    protected function endWeeklySpawnEvent(Event $event): void
    {
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
     * @param FactionLoyaltyService $factionLoyaltyService
     * @param Event $event
     * @return void
     */
    protected function endWinterEvent(KingdomEventService $kingdomEventService,
                                      TraverseService $traverseService,
                                      ExplorationAutomationService $explorationAutomationService,
                                      FactionLoyaltyService $factionLoyaltyService,
                                      Event $event): void {

        $kingdomEventService->handleKingdomRewardsForEvent(MapNameValue::ICE_PLANE);

        $gameMap    = GameMap::where('name', MapNameValue::ICE_PLANE)->first();
        $faction    = Faction::where('game_map_id', $gameMap->id)->first();
        $surfaceMap = GameMap::where('name', MapNameValue::SURFACE)->first();

        Character::select('characters.*')
            ->join('maps', 'maps.character_id', '=', 'characters.id')
            ->where('maps.game_map_id', $gameMap->id)
            ->chunk(100, function ($characters) use (
                $traverseService,
                $surfaceMap,
                $explorationAutomationService,
                $factionLoyaltyService,
                $faction,
                $gameMap
            ) {
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

                    $this->unpledgeFromTheMapsFaction($character, $factionLoyaltyService, $faction);
                }
            });

        event(new GlobalMessageEvent('The Queen of Ice calls forth her twisted memories and magics to seal the gates to her realm. "My son! You have stolen the memories of my son!" She bellows as she banishes you and others from her realm!'));

        $announcement = Announcement::where('event_id', $event->id)->first();

        event(new DeleteAnnouncementEvent($announcement->id));

        $announcement->delete();

        $event->delete();

        $this->cleanUpEventGoals();

        $this->updateAllCharacterStatuses();
    }

    /**
     * End the delusional memories' event.
     *
     * @param KingdomEventService $kingdomEventService
     * @param TraverseService $traverseService
     * @param ExplorationAutomationService $explorationAutomationService
     * @param FactionLoyaltyService $factionLoyaltyService
     * @param Event $event
     * @return void
     */
    protected function endDelusionalEvent(KingdomEventService $kingdomEventService,
                                      TraverseService $traverseService,
                                      ExplorationAutomationService $explorationAutomationService,
                                      FactionLoyaltyService $factionLoyaltyService,
                                      Event $event): void {

        $kingdomEventService->handleKingdomRewardsForEvent(MapNameValue::DELUSIONAL_MEMORIES);

        $gameMap    = GameMap::where('name', MapNameValue::DELUSIONAL_MEMORIES)->first();
        $faction    = Faction::where('game_map_id', $gameMap->id)->first();
        $surfaceMap = GameMap::where('name', MapNameValue::SURFACE)->first();

        Character::select('characters.*')
            ->join('maps', 'maps.character_id', '=', 'characters.id')
            ->where('maps.game_map_id', $gameMap->id)
            ->chunk(100, function ($characters) use (
                $traverseService,
                $surfaceMap,
                $explorationAutomationService,
                $factionLoyaltyService,
                $faction,
                $gameMap
            ) {
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

                    $this->unpledgeFromTheMapsFaction($character, $factionLoyaltyService, $faction);
                }
            });

        event(new GlobalMessageEvent('The voice of Fliniguss echos in your ears: "Child, I grow weary of your games." The twisted mother laughs: Ooooh hooo hooo hoo. A chill falls in the air.'));

        $announcement = Announcement::where('event_id', $event->id)->first();

        event(new DeleteAnnouncementEvent($announcement->id));

        $announcement->delete();

        $event->delete();

        $this->cleanUpEventGoals();

        $this->updateAllCharacterStatuses();
    }

    private function updateAllCharacterStatuses(): void {
        Character::chunkById(250, function($characters) {
            foreach ($characters as $character) {
                event(new UpdateCharacterStatus($character));
            }
        });
    }

    /**
     * Remove the pledge and helping Npc from the character for the map ending.
     *
     * @param Character $character
     * @param FactionLoyaltyService $factionLoyaltyService
     * @param Faction|null $faction
     * @return void
     */
    private function unpledgeFromTheMapsFaction(Character $character, FactionLoyaltyService $factionLoyaltyService, ?Faction $faction = null): void {
        if (!is_null($faction)) {
            $factionLoyalty = $character->factionLoyalties()
                ->where('faction_id', $faction->id)
                ->first();

            $assistingNpc = $factionLoyalty
                ->factionLoyaltyNpcs()
                ->where('currently_helping', true)
                ->first();

            if (!is_null($assistingNpc)) {
                $factionLoyaltyService->stopAssistingNpc($character, $assistingNpc);
            }

            $factionLoyaltyService->removePledge($character, $faction);
        }
    }

    /**
     * Clean up Global Event goal stuff.
     *
     * @return void
     */
    private function cleanUpEventGoals(): void {
        GlobalEventParticipation::truncate();
        GlobalEventGoal::truncate();
        GlobalEventCraftingInventorySlot::truncate();
        GlobalEventCraftingInventorySlot::truncate();
        GlobalEventKill::truncate();
        GlobalEventCraft::truncate();
        GlobalEventEnchant::truncate();
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
