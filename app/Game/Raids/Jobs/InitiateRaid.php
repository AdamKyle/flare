<?php

namespace App\Game\Raids\Jobs;

use App\Flare\Events\UpdateScheduledEvents;
use App\Flare\Models\Event;
use App\Flare\Models\Location;
use App\Flare\Models\Raid;
use App\Flare\Models\ScheduledEvent;
use App\Flare\Services\EventSchedulerService;
use App\Flare\Values\EventType;
use App\Game\Maps\Services\LocationService;
use App\Game\Raids\Events\CorruptLocations;
use Facades\App\Game\Core\Handlers\AnnouncementHandler;
use App\Game\Core\Traits\UpdateMarketBoard;
use App\Game\Messages\Events\GlobalMessageEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class InitiateRaid implements ShouldQueue {

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, UpdateMarketBoard;

    /**
     * @var array $raidStory
     */
    protected array $raidStory = [];

    /**
     * @var int $raidId
     */
    protected int $raidId;

    /**
     * @var int $eventId
     */
    protected int $eventId;

    /**
     * Create a new job instance.
     *
     * @param int $raidId
     * @param array $raidStory
     */
    public function __construct(int $eventId, int $raidId, array $raidStory = []) {
        $this->eventId   = $eventId;
        $this->raidId    = $raidId;
        $this->raidStory = $raidStory;
    }

    /**
     * @param LocationService $locationService
     * @return void
     */
    public function handle(LocationService $locationService, EventSchedulerService $eventSchedulerService): void {

        if (empty($this->raidStory)) {

            $raid = Raid::find($this->raidId);

            $this->initializeRaid($raid, $locationService, $eventSchedulerService);

            return;
        }

        event(new GlobalMessageEvent(array_shift($this->raidStory), 'raid-global-message'));

        InitiateRaid::dispatch($this->raidId, $this->raidStory)->delay(now()->addSeconds(30));
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
     * Initialize the raid
     *
     * @param Raid $raid
     * @param LocationService $locationService
     * @return void
     */
    private function initializeRaid(Raid $raid, LocationService $locationService, EventSchedulerService $eventSchedulerService): void {
        if (empty($raid->corrupted_location_ids)) {
            return;
        }

        Location::findMany('id', $raid->corrupted_location_ids)->update([
            'is_corrupted' => true,
            'raid_id'      => $raid->id,
        ]);

        $locationNames    = Location::findMany('id', $raid->corrupted_location_ids)->pluck('name')->toArray();
        $locationMapNames = Location::findMany('id', $raid->corrupted_location_ids)->pluck('gameMap.name')->toArray();

        event(new GlobalMessageEvent('Locations: ' . implode(', ', $locationNames) . ' on the planes: ' .
            implode(', ', $locationMapNames) . ' have become corrupted with foul critters!'));

        $locationOfRaidBoss = Location::find($raid->raid_boss_id);

        $locationOfRaidBoss->update([
            'is_corrupted'  => true,
            'raid_id'       => $raid->id,
            'has_raid_boss' => true,
        ]);

        event(new GlobalMessageEvent('Location: ' . $locationOfRaidBoss->namw . ' At (X/Y): '.$locationOfRaidBoss->x.
            '/'.$locationOfRaidBoss->y.' on plane: ' . $locationOfRaidBoss->gameMap->name . ' has become over run! The Raid boss: '.$raid->raidBoss->name.
            ' has set up shop!'));

        $endDate = now(config('app.timezone'))->addDays(13)->setTime(12, 0);
        $formattedDate = $endDate->format('l, j \of F \a\t h:ia \G\M\TP');

        Event::create([
            'type'        => EventType::RAID_EVENT,
            'started_at'  => now(),
            'ends_at'     => $endDate,
            'raid_id'     => $raid->id,
        ]);

        $this->updateCorruptLocations($raid, $locationService);

        event(new GlobalMessageEvent('Raid has started! and will end on: ' . $formattedDate));

        AnnouncementHandler::createAnnouncement('raid_announcement');

        ScheduledEvent::find($this->eventId)->update([
            'currently_running' => true
        ]);

        event(new UpdateScheduledEvents($eventSchedulerService->fetchEvents()));
    }
}
