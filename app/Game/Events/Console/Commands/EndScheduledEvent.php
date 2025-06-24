<?php

namespace App\Game\Events\Console\Commands;

use App\Flare\Events\UpdateScheduledEvents;
use App\Flare\Models\Announcement;
use App\Flare\Models\Character;
use App\Flare\Models\Event;
use App\Flare\Models\Faction;
use App\Flare\Models\GameMap;
use App\Flare\Models\GlobalEventCraft;
use App\Flare\Models\GlobalEventCraftingInventorySlot;
use App\Flare\Models\GlobalEventEnchant;
use App\Flare\Models\GlobalEventGoal;
use App\Flare\Models\GlobalEventKill;
use App\Flare\Models\GlobalEventParticipation;
use App\Flare\Models\Location;
use App\Flare\Models\Raid;
use App\Flare\Models\RaidBoss;
use App\Flare\Models\RaidBossParticipation;
use App\Flare\Models\ScheduledEvent;
use App\Flare\Models\SubmittedSurvey;
use App\Flare\Services\CreateSurveySnapshot;
use App\Flare\Services\EventSchedulerService;
use App\Flare\Values\MapNameValue;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Core\Values\FactionLevel;
use App\Game\Events\Services\KingdomEventService;
use App\Game\Events\Values\EventType;
use App\Game\Exploration\Services\ExplorationAutomationService;
use App\Game\Factions\FactionLoyalty\Services\FactionLoyaltyService;
use App\Game\Maps\Services\LocationService;
use App\Game\Maps\Services\TraverseService;
use App\Game\Maps\Services\UpdateRaidMonsters;
use App\Game\Messages\Events\DeleteAnnouncementEvent;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Quests\Services\BuildQuestCacheService;
use App\Game\Raids\Events\CorruptLocations;
use App\Game\Survey\Events\ShowSurvey;
use Exception;
use Illuminate\Console\Command;

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
     * @param  KingdomEventService  $kingdomEventService  ,
     * @param  TraverseService  $traverseService  ,
     *
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
        CreateSurveySnapshot $createSurveySnapshot
    ): void {
        $this->endScheduledEvent(
            $locationService,
            $updateRaidMonsters,
            $eventSchedulerService,
            $kingdomEventService,
            $traverseService,
            $explorationAutomationService,
            $buildQuestCacheService,
            $factionLoyaltyService,
            $createSurveySnapshot,
        );
    }

    /**
     * End the scheduled events who are supposed to end.
     *
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
        FactionLoyaltyService $factionLoyaltyService,
        CreateSurveySnapshot $createSurveySnapshot
    ): void {

        $scheduledEvents = ScheduledEvent::where('end_date', '<=', now())->where('currently_running', true)->get();

        foreach ($scheduledEvents as $event) {

            $currentEvent = Event::where('type', $event->event_type)->where('ends_at', '<=', now())->first();

            if (is_null($currentEvent)) {

                $event->update([
                    'currently_running' => false,
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

            if ($eventType->isFeedbackEvent()) {

                $this->endFeedBackEvent($createSurveySnapshot);

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
     * @return void
     */
    protected function endRaid(ScheduledEvent $event, LocationService $locationService, UpdateRaidMonsters $updateRaidMonsters)
    {

        $raid = $event->raid;

        event(new GlobalMessageEvent('The Raid: ' . $raid->name . ' is now ending! Don\'t worry, the raid will be back soon. Check the event calendar for the next time!'));

        $this->unCorruptLocations($raid, $locationService);

        $event = Event::where('raid_id', $raid->id)->first();

        RaidBossParticipation::where('raid_id', $raid->id)->delete();

        RaidBoss::where('raid_id', $raid->id)->delete();

        $this->updateMonstersForCharactersAtRaidLocations($raid, $updateRaidMonsters);

        if (! is_null($event)) {
            $announcement = Announcement::where('event_id', $event->id)->first();

            if (! is_null($announcement)) {
                event(new DeleteAnnouncementEvent($announcement->id));

                $announcement->delete();
            }

            $event->delete();
        }
    }

    /**
     * Ends a weekly currency event
     */
    protected function endWeeklyCurrencyDrops(Event $event): void
    {

        event(new GlobalMessageEvent('Weekly currency drops have come to an end! Come back next sunday for another chance!'));

        $announcement = Announcement::where('event_id', $event->id)->first();

        event(new DeleteAnnouncementEvent($announcement->id));

        $announcement->delete();

        $event->delete();
    }

    /**
     * End Faction Loyalty Event.
     */
    protected function endWeeklyFactionLoyaltyEvent(Event $event): void
    {

        event(new GlobalMessageEvent('Weekly Faction Loyalty Event has come to an end. Next time Npc Tasks refresh from level up, they will be back to normal.'));

        $announcement = Announcement::where('event_id', $event->id)->first();

        event(new DeleteAnnouncementEvent($announcement->id));

        $announcement->delete();

        $event->delete();
    }

    /**
     * End Weekly Celestial Spawn Event
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
     */
    protected function endWinterEvent(
        KingdomEventService $kingdomEventService,
        TraverseService $traverseService,
        ExplorationAutomationService $explorationAutomationService,
        FactionLoyaltyService $factionLoyaltyService,
        Event $event
    ): void {

        $kingdomEventService->handleKingdomRewardsForEvent(MapNameValue::ICE_PLANE);

        $gameMap = GameMap::where('name', MapNameValue::ICE_PLANE)->first();
        $faction = Faction::where('game_map_id', $gameMap->id)->first();
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
                        'current_level' => 0,
                        'current_points' => 0,
                        'points_needed' => FactionLevel::getPointsNeeded(0),
                        'maxed' => false,
                        'title' => null,
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
     */
    protected function endDelusionalEvent(
        KingdomEventService $kingdomEventService,
        TraverseService $traverseService,
        ExplorationAutomationService $explorationAutomationService,
        FactionLoyaltyService $factionLoyaltyService,
        Event $event
    ): void {

        $kingdomEventService->handleKingdomRewardsForEvent(MapNameValue::DELUSIONAL_MEMORIES);

        $gameMap = GameMap::where('name', MapNameValue::DELUSIONAL_MEMORIES)->first();
        $faction = Faction::where('game_map_id', $gameMap->id)->first();
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
                        'current_level' => 0,
                        'current_points' => 0,
                        'points_needed' => FactionLevel::getPointsNeeded(0),
                        'maxed' => false,
                        'title' => null,
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

    protected function endFeedBackEvent(CreateSurveySnapshot $createSurveySnapshot): void
    {
        event(new GlobalMessageEvent('The Creator thanks all his players for their valuable feedback. At this time the survey has closed! Feedback is being gathered as we speak'));

        $createSurveySnapshot->createSnapShop();

        SubmittedSurvey::truncate();

        Character::chunkById(250, function ($characters) {
            foreach ($characters as $character) {
                $character->user()->update([
                    'is_showing_survey' => false,
                ]);

                $character = $character->refresh();

                event(new ShowSurvey($character->user));
            }
        });

        event(new GlobalMessageEvent('Survey stats have been generated. The Creator has yet to leave a response. You can see these stats by
        refreshing and clicking the left side bar, there will be a new menu option for the survey stats. Once The Creator has a chance to look
        at them, you will find a button at the bottom called The Creators Response, this will be a detailed post about how the stats impact the
        direction Tlessa goes in, in order for it be the best PBBG out there!'));
    }

    private function updateAllCharacterStatuses(): void
    {
        Character::chunkById(250, function ($characters) {
            foreach ($characters as $character) {
                event(new UpdateCharacterStatus($character));
            }
        });
    }

    /**
     * Remove the pledge and helping Npc from the character for the map ending.
     */
    private function unpledgeFromTheMapsFaction(Character $character, FactionLoyaltyService $factionLoyaltyService, ?Faction $faction = null): void
    {
        if (! is_null($faction)) {
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
                $factionLoyaltyService->stopAssistingNpc($character, $assistingNpc);
            }

            $factionLoyaltyService->removePledge($character, $faction);
        }
    }

    /**
     * Clean up Global Event goal stuff.
     */
    private function cleanUpEventGoals(): void
    {
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
     * @return void
     */
    private function unCorruptLocations(Raid $raid, LocationService $locationService)
    {
        $raidLocations = [...$raid->corrupted_location_ids, $raid->raid_boss_location_id];

        Location::whereIn('id', $raidLocations)->update([
            'is_corrupted' => false,
            'raid_id' => null,
            'has_raid_boss' => false,
        ]);

        event(new CorruptLocations($locationService->fetchCorruptedLocationData($raid)));
    }

    /**
     * Update monsters for the characters at raid locations.
     */
    private function updateMonstersForCharactersAtRaidLocations(Raid $raid, UpdateRaidMonsters $updateRaidMonsters): void
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
                $updateRaidMonsters->updateMonstersForRaidLocations($character, $location);
            }
        }
    }
}
