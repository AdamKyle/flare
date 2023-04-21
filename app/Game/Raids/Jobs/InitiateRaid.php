<?php

namespace App\Game\Raids\Jobs;

use App\Flare\Models\Event;
use App\Flare\Models\Location;
use App\Flare\Models\Raid;
use App\Flare\Values\EventType;
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
     * Create a new job instance.
     *
     * @param int $raidId
     * @param array $raidStory
     */
    public function __construct(int $raidId, array $raidStory = []) {
        $this->raidId    = $raidId;
        $this->raidStory = $raidStory;
    }

    /**
     * @return void
     */
    public function handle(): void {

        if (empty($this->raidStory)) {

            $raid = Raid::find($this->raidId);

            $this->corruptLocations($raid);

            return;
        }

        event(new GlobalMessageEvent(array_shift($this->raidStory), 'raid-global-message'));

        InitiateRaid::dispatch($this->raidId, $this->raidStory)->delay(now()->addSeconds(30));
    }

    private function corruptLocations(Raid $raid): void {
        if (empty($raid->corrupted_location_ids)) {
            return;
        }

        Location::whereIn('id', $raid->corrupted_location_ids)->update([
            'is_corrupted' => true,
            'raid_id'      => $raid->id,
        ]);

        $locationNames    = Location::whereIn('id', $raid->corrupted_location_ids)->pluck('name')->toArray();
        $locationMapNames = Location::whereIn('id', $raid->corrupted_location_ids)->pluck('gameMap.name')->toArray();

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

        event(new GlobalMessageEvent('Raid has started! and will end on: ' . $formattedDate));

        AnnouncementHandler::createAnnouncement('raid_announcement');
    }
}
