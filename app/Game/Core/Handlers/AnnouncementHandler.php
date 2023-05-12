<?php

namespace App\Game\Core\Handlers;

use App\Flare\Models\Announcement;
use App\Flare\Models\Event;
use App\Flare\Models\GameMap;
use App\Flare\Models\Location;
use App\Flare\Models\Raid;
use App\Flare\Values\EventType;
use App\Game\Messages\Events\AnnouncementMessageEvent;
use Exception;

class AnnouncementHandler {

    public function createAnnouncement(string $type): void {
        $this->buildAnnouncementForType($type);
    }

    protected function buildAnnouncementForType(string $type): void {
        match ($type) {
            'raid_announcement' => $this->buildRaidAnnouncementMessage(),
            default => throw new Exception('Cannot determine announcement type'),
        };
    }

    private function buildRaidAnnouncementMessage(): void {
        $event = Event::where('type', EventType::RAID_EVENT)->first();

        if (is_null($event)) {
            throw new Exception('Cannot create message for raid event, when no event exists.');
        }

        $raid = Raid::find($event->raid_id);

        $locationNames      = Location::whereIn('id', $raid->corrupted_location_ids)->pluck('name')->toArray();
        $gameMapIds         = Location::whereIn('id', $raid->corrupted_location_ids)->pluck('game_map_id')->toArray();
        $gameMapNames       = array_unique(GameMap::whereIn('id', $gameMapIds)->pluck('name')->toArray());
        $locationOfRaidBoss = Location::find($raid->raid_boss_location_id);

        $message = 'There is a riad ('.$raid->name.') currently running that ends on: ' . $event->ends_at->format('l, j \of F \a\t h:ia \G\M\TP') .
            '. Corrupted location are at: ' . implode(', ', $locationNames) . ' on the planes: ' . implode(', ', $gameMapNames).
            '. While the boss ('.$raid->raidBoss->name.') is at: ' . $locationOfRaidBoss->name . ' At (X/Y): '.$locationOfRaidBoss->x.
            '/'.$locationOfRaidBoss->y.' on plane: ' . $locationOfRaidBoss->map->name . '.';

        Announcement::create([
            'message'    => $message,
            'expires_at' => $event->ends_at
        ]);

        event(new AnnouncementMessageEvent($message));
    }
}
