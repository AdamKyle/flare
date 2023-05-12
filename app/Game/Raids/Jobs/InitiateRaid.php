<?php

namespace App\Game\Raids\Jobs;

use App\Flare\Models\Raid;
use App\Flare\Models\Event;
use App\Flare\Models\GameMap;
use Illuminate\Bus\Queueable;
use App\Flare\Models\Location;
use App\Flare\Models\Character;
use App\Flare\Values\EventType;
use App\Flare\Models\ScheduledEvent;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use App\Flare\Events\UpdateScheduledEvents;
use App\Game\Maps\Services\LocationService;
use App\Game\Raids\Events\CorruptLocations;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Game\Maps\Services\UpdateRaidMonsters;
use App\Flare\Services\EventSchedulerService;
use App\Game\Messages\Events\GlobalMessageEvent;
use Facades\App\Game\Core\Handlers\AnnouncementHandler;

class InitiateRaid implements ShouldQueue {

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var array $raidStory
     */
    protected array $raidStory = [];

    /**
     * @var int $eventId
     */
    protected int $eventId;

    /**
     * Create a new job instance.
     *
     * @param int $eventId
     * @param array $raidStory
     */
    public function __construct(int $eventId, array $raidStory = []) {
        $this->eventId   = $eventId;
        $this->raidStory = $raidStory;
    }

    /**
     * @param LocationService $locationService
     * @return void
     */
    public function handle(LocationService $locationService, 
                           EventSchedulerService $eventSchedulerService, 
                           UpdateRaidMonsters $updateRaidMonsters): void 
    {

        $event = ScheduledEvent::find($this->eventId);

        if (empty($this->raidStory)) {

            $raid = Raid::find($event->raid_id);

            $this->initializeRaid($raid, $locationService, $eventSchedulerService, $updateRaidMonsters);

            return;
        }

        event(new GlobalMessageEvent(array_shift($this->raidStory), 'raid-global-message'));

        InitiateRaid::dispatch($event->id, $this->raidStory)->delay(now()->addSeconds(30));
    }

    /**
     * Initialize the raid
     *
     * @param Raid $raid
     * @param LocationService $locationService
     * @return void
     */
    protected function initializeRaid(Raid $raid, 
                                    LocationService $locationService, 
                                    EventSchedulerService $eventSchedulerService, 
                                    UpdateRaidMonsters $updateRaidMonsters): void 
    {
        if (empty($raid->corrupted_location_ids)) {
            return;
        }

        $this->corruptLocations($raid, $locationService);

        $endDate = $this->createEvent($eventSchedulerService);

        $this->updateMonstersForCharactersAtRaidLocations($raid, $updateRaidMonsters);

        event(new GlobalMessageEvent('Raid has started! and will end on: ' . $endDate));

        AnnouncementHandler::createAnnouncement('raid_announcement');
    }

    /**
     * Update the monster list for those who are at the raid location.
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

    /**
     * Corrupt locations for the raid.
     *
     * @param Raid $raid
     * @param LocationService $locationService
     * @return void
     */
    private function updateCorruptLocations(Raid $raid, LocationService $locationService): void {
        event(new CorruptLocations($locationService->fetchCorruptedLocationData($raid)->toArray()));
    }

    /**
     * Corrupt and update corrupted locations.
     *
     * @param Raid $raid
     * @param LocationService $locationService
     * @return void
     */
    private function corruptLocations(Raid $raid, LocationService $locationService) {
        Location::whereIn('id', $raid->corrupted_location_ids)->update([
            'is_corrupted' => true,
            'raid_id'      => $raid->id,
        ]);

        $locationNames    = Location::whereIn('id', $raid->corrupted_location_ids)->pluck('name')->toArray();
        $locationMapIds   = Location::whereIn('id', $raid->corrupted_location_ids)->pluck('game_map_id')->toArray();
        $locationMapNames = array_unique(GameMap::whereIn('id', $locationMapIds)->pluck('name')->toArray());

        event(new GlobalMessageEvent('Locations: ' . implode(', ', $locationNames) . ' on the planes: ' .
            implode(', ', $locationMapNames) . ' have become corrupted with foul critters!'));

        $locationOfRaidBoss = Location::find($raid->raid_boss_location_id);

        $locationOfRaidBoss->update([
            'is_corrupted'  => true,
            'raid_id'       => $raid->id,
            'has_raid_boss' => true,
        ]);

        event(new GlobalMessageEvent('Location: ' . $locationOfRaidBoss->name . ' At (X/Y): '.$locationOfRaidBoss->x.
            '/'.$locationOfRaidBoss->y.' on plane: ' . $locationOfRaidBoss->map->name . ' has become over run! The Raid boss: '.$raid->raidBoss->name.
            ' has set up shop!'));

        $this->updateCorruptLocations($raid, $locationService);
    }

    /**
     * Create the event.
     * 
     * - Update the scheduled event to currently running.
     * - Create a new event record
     * - Update the calendar with the updated scheduled events.
     * - Returns the end date - formatted.
     *
     * @param EventSchedulerService $eventSchedulerService
     * @return string
     */
    private function createEvent(EventSchedulerService $eventSchedulerService): string {

        $scheduledEvent = ScheduledEvent::find($this->eventId);

        $formattedDate = $scheduledEvent->end_date->format('l, j \of F \a\t h:ia \G\M\TP');

        Event::create([
            'type'        => EventType::RAID_EVENT,
            'started_at'  => now(),
            'ends_at'     => $scheduledEvent->end_date,
            'raid_id'     => $scheduledEvent->id,
        ]);

        $scheduledEvent->update([
            'currently_running' => true,
        ]);

        event(new UpdateScheduledEvents($eventSchedulerService->fetchEvents()));

        return $formattedDate;
    }
}
