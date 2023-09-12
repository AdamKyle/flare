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
            'monthly_pvp' => $this->buildMonthlyPVPMessage(),
            'weekly_celestial_spawn' => $this->buildWeeklyCelestialMessage(),
            'weekly_currency_drop' => $this->buildWeeklyCurrencyDrop(),
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

        $message = 'There is a riad (' . $raid->name . ') currently running that ends on: ' . $event->ends_at->format('l, j \of F \a\t h:ia \G\M\TP') .
            '. Corrupted location are at: ' . implode(', ', $locationNames) . ' on the planes: ' . implode(', ', $gameMapNames) .
            '. While the boss (' . $raid->raidBoss->name . ') is at: ' . $locationOfRaidBoss->name . ' At (X/Y): ' . $locationOfRaidBoss->x .
            '/' . $locationOfRaidBoss->y . ' on plane: ' . $locationOfRaidBoss->map->name . '.';

        Announcement::create([
            'message'    => $message,
            'expires_at' => $event->ends_at,
            'event_id'   => $event->id,
        ]);

        event(new AnnouncementMessageEvent($message));
    }

    private function buildMonthlyPVPMessage(): void {
        $event = Event::where('type', EventType::MONTHLY_PVP)->first();

        if (is_null($event)) {
            throw new Exception('Cannot create message for monthly pvp event, when no event exists.');
        }

        $message = 'Monthly PVP will start at 6pm GMT-6!! Afterwords the Celestial Kings will spawn!! ' .
            'To participate please click the Join PVP in the action section or from the mobile action drop down selection.' .
            'At 6pm GMT-6 players who have opted in will be automatically moved to the colosseum where they will auto fight ' .
            'in a matched pvp event. After the last player is left standing he/she will be rewarded with a mythic and the Celestial Kings, ' .
            'who can also drop mythics. These Beings will only be around for an hour after the main PVP event!';

        Announcement::create([
            'message'    => $message,
            'expires_at' => $event->ends_at,
            'event_id'   => $event->id
        ]);

        event(new AnnouncementMessageEvent($message));
    }

    private function buildWeeklyCelestialMessage(): void {
        $event = Event::where('type', EventType::WEEKLY_CELESTIALS)->first();

        if (is_null($event)) {
            throw new Exception('Cannot create message for weekly celestial event, when no event exists.');
        }

        $message = 'Celestials have been unleashed across the lands and various planes! All you have to do, for the next 24 hours ' .
            'ending at: ' . $event->ends_at->format('l, j \of F \a\t h:ia \G\M\TP') . ' players just have to move around the map and there is a 80% ' .
            'chance for Celestial Entities that you would otherwise have to pay to conjure, will spawn! Kill em all child and get those pretty shards for alchemy!';

        Announcement::create([
            'message'    => $message,
            'expires_at' => $event->ends_at,
            'event_id'   => $event->id
        ]);

        event(new AnnouncementMessageEvent($message));
    }

    private function buildWeeklyCurrencyDrop(): void {
        $event = Event::where('type', EventType::WEEKLY_CURRENCY_DROPS)->first();

        if (is_null($event)) {
            throw new Exception('Cannot create message for weekly celestial event, when no event exists.');
        }

        $message = 'For one day only, ending: ' . $event->ends_at->format('l, j \of F \a\t h:ia \G\M\TP') . ' ' .
            'Players can get 1-50 of each type of currency, Gold Dust, Crystal Shards, Copper Coins (if you have the appropriate quest item). ';

        Announcement::create([
            'message'    => $message,
            'expires_at' => $event->ends_at,
            'event_id'   => $event->id
        ]);

        event(new AnnouncementMessageEvent($message));
    }
}
